import csv, sqlite3, glob, sys, os

DB_NAME = "cut_scene.db"

scheme_switch = {
        "subtitle" : 
            {"cols": ['begin', 'end', 'text'],
            "types": [float, float, str],
            "time_start": "begin",
            "time_end": "end"},
        "transcript-lium":
            {"cols": ['filename', 'sdr', 'start', 'duration', 'word', 'confidence'],
            "types": [str, float, float, float, str, float],
            "time_start": "start",
            "length": "duration"},
        "transcript-NST":
            {"cols": ["segment_end","segment_speakerid","segment_start","word_end","word_start","word"],
            "types": [float, int, float, float, float, str],
            "time_start": "word_start",
            "time_end": "word_end"},
        "transcript-limsi":
            {"cols": ["SpeechSegment_ch", "SpeechSegment_time", "SpeechSegment_lang", "SpeechSegment_lconf","SpeechSegment_sconf","SpeechSegment_spkid","SpeechSegment_stime","SpeechSegment_trs","Word_conf","Word_dur","Word_stime","Word"],
            "types": [int, float, str, float, float, str, float, int, float, float, float, str],
            "time_start": "Word_stime",
            "length": "Word_dur"
            },
        "tar.scenecut":
            {"cols": ['filename', 'frameid'],
            "types": [str, int],
            "time_start": "start",
            "length": "duration"}
        }

def main():

    if len(sys.argv) < 3:
        print("Usage %s <input dir> <out dir>" % sys.argv[0])
        sys.exit(-1)

    inp = sys.argv[1]
    output_dir = sys.argv[2]

    print("input dir: %s" % inp)
    print("out dir: %s" % output_dir)

    db_con, db_cur = init_db()

    # enumerating the base video names
    flst = glob.glob("%s/*.tar.info.csv" % inp)
    print(flst)
    for f in flst:
        base = os.path.split(f)[-1]
        base = base.split(".")[0]
        print("processing %s" % base)
        # using the video names enumerating the feature csv files like transcript.csv etc.
        csvfiles = glob.glob("%s/%s.*.csv" % (inp, base))

        # hell yeah, syntactic anti-sugar to the rescue - building a dictionary "filetype" -> "filename"
        proc_dict = {".".join(os.path.split(descr)[-1].split(".")[1:-1]) : descr
            for descr in csvfiles}

        cut_points = []

        # reading the scenecut points
        if "tar.info" in proc_dict and "tar.scenecut" in proc_dict:
            with open(proc_dict["tar.info"]) as info_file, open(proc_dict["tar.scenecut"]) as cut_file:
                infocsv = csv.DictReader(info_file, delimiter=";")
                cutcsv = csv.DictReader(cut_file, delimiter=";")
                fps = float(infocsv.next()["fps"])
                cut_points = [float(row["frameid"])/fps for row in cutcsv]
                # TODO: append last one
                cut_points.insert(0, 0.0)
        else:
            print("warn: no info or scenecut file for %s" % base)
            continue

        del proc_dict["tar.info"]
        del proc_dict["tar.scenecut"]

        for k in proc_dict.keys():
            # csv -> sqlite3
            if k not in scheme_switch:
                print("warn: can not handle descr_type %s" % k)
            else:
                print("processing %s" % k)
                db_append_by_scheme(k, scheme_switch[k], proc_dict[k], base, db_con, db_cur)
                for i in range(len(cut_points)-1):
                    f0, f1 = cut_points[i], cut_points[i+1]
                    write_time_interval_file(base, k, scheme_switch[k], (f0, f1), db_con, db_cur, output_dir, i)

    finit_db(db_con)

def init_db():
    con = sqlite3.connect(DB_NAME)
    con.text_factory = str
    cur = con.cursor()
    return con, cur

def finit_db(con):
    con.close()

def py2sqlite(tplst):
    tpdict = {float: "REAL", str: "TEXT", int: "INTEGER"}
    return [tpdict[i] for i in tplst]

def form_table_name(orig):
    table_name = orig.replace(".", "_")
    table_name = table_name.replace("-", "_")
    return table_name

def db_append_by_scheme(descr_type, scheme, csvfile, base_fname, db_con, db_cur):
    # some question marks for the insert query
    qmarks = ",".join(["?"]*(1+len(scheme["cols"])))
    # table scheme
    field_lst = ["_filename_ TEXT",]
    field_lst.extend( ["%s %s" % (v, t) for v, t in zip(scheme["cols"], py2sqlite(scheme["types"]))] )
    decl = ",".join(field_lst)

    col_lst = ["_filename_",]
    col_lst.extend([x for x in scheme["cols"]])
    cols = ",".join(col_lst)
    table_name = form_table_name(descr_type)

    print(decl)
    print(cols)

    db_cur.execute("CREATE TABLE IF NOT EXISTS %s (%s);" % (table_name, decl))

    to_db = []
    with open(csvfile, 'rb') as fin:
        dr = csv.DictReader(fin, delimiter=";")
        for row in dr:
            try:
                r = [base_fname]
                r.extend([typ(row[col]) for col, typ in zip(scheme["cols"], scheme["types"])])
                tp = tuple(r)
                to_db.append(tp)
            except:
                print("skipping a row because of some error")
                print(scheme["cols"])
                print(row)

    # TODO: check if we already have this row in the db
    print(table_name)
    print(cols)
    print(qmarks)
    #print(to_db)
    db_cur.executemany("INSERT INTO %s (%s) VALUES (%s);" % (table_name, cols, qmarks), to_db)
    db_con.commit()

def write_time_interval_file(base_fname, descr_type, scheme, interval, db_con, db_cur, out_dir, cnt):
    # sql query for filtering time interval
    table_name = form_table_name(descr_type)
    if "time_end" in scheme:
        query = "SELECT * FROM %s WHERE %s._filename_ == '%s' AND %s<%s.'%s' AND %s.'%s'<%s ORDER BY %s.'%s'" % (table_name, table_name, base_fname, interval[0], table_name, scheme["time_start"], table_name, scheme["time_end"], interval[1], table_name, scheme["time_start"])
    elif "length" in scheme:
        query = "SELECT * FROM %s WHERE %s._filename_ == '%s' AND %s<%s.'%s' AND %s.'%s'+%s.'%s'<%s ORDER BY %s.'%s'" % (table_name, table_name, base_fname, interval[0], table_name, scheme["time_start"], table_name, scheme["time_start"], table_name, scheme["length"], interval[1], table_name, scheme["time_start"])
    else:
        print("warn: not time information %s" % descr_type)
        return

    db_cur.execute(query)
    data = db_cur.fetchall()
    dst = "%s/%s/%s" % (out_dir, base_fname, descr_type)
    if not os.path.exists(dst):
        os.makedirs(dst)
    with open("%s/%s_%s_%03d.csv" % (dst, base_fname, descr_type, cnt), "w") as f:
        all_cols = ["_filename_"]
        all_cols.extend([k for k in scheme["cols"]])
        csvhead = ";".join(all_cols)
        f.write(csvhead + "\n")
        for row in data:
            f.write(";".join([str(x) for x in row]) + "\n")

if __name__ == "__main__":
    main()

