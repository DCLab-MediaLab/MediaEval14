import csv, sqlite3, glob, sys, os

def main():

	if len(sys.argv) < 2:
		print("Usage %s <input dir>" % sys.argv[0])
		sys.exit(-1)

	inp = sys.argv[1]

	print("input dir: %s" % inp)

	flst = glob.glob("%s/*.tar.info.csv" % inp)

	for f in flst:
		base = os.path.split(f)[-1]
		base = base.split(".")[0]
		print("processing %s" % base)
		csvfiles = glob.glob("%s/%s.*.csv" % (inp, base))

		# hell yeah, syntactic anti-sugar to the rescue
		proc_dict = {".".join(os.path.split(descr)[-1].split(".")[1:-1]) : descr
			for descr in csvfiles}

		cut_points = []

		if "tar.info" in proc_dict and "tar.scenecut" in proc_dict:
			with open(proc_dict["tar.info"]) as info_file, open(proc_dict["tar.scenecut"]) as cut_file:
				infocsv = csv.DictReader(info_file, delimiter=";")
				cutcsv = csv.DictReader(cut_file, delimiter=";")
				fps = float(infocsv.next()["fps"])
				cut_points = [float(row["frameid"])*fps for row in cutcsv]
				# TODO: append last one
				cut_points.insert(0, 0.0)
		else:
			print("warn: no info or scenecut file for %s" % base)
			continue

		del proc_dict["tar.info"]
		del proc_dict["tar.scenecut"]

		for k in proc_dict.keys():
			build_type(k, proc_dict[k])
			#for i in range(len(cut_points)-1):
			#	f0, f1 = cut_points[i], cut_points[i+1]
			#	print("cutting interval %f - %f" % (f0, f1))
			#	process_type(k, proc_dict[k], (f0, f1), "outtt")

def build_type(descr_type, filename):
	print("processing %s %s" % (descr_type, filename))
	switch = {
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
			"tar.scenecut":
				{"cols": ['filename', 'frameid'],
				"types": [str, int],
				"time_start": "start",
				"length": "duration"}
			}
	if descr_type not in switch:
		print("warn: can not handle descr_type %s" % descr_type)
	else:
		cur = build_by_scheme(descr_type, switch[descr_type], filename)
		#write_time_interval_file(cur, interval, switch[descr_type], out_dir)

def py2sqlite(tplst):
	tpdict = {float: "REAL", str: "TEXT", int: "INTEGER"}
	return [tpdict[i] for i in tplst]

def build_by_scheme(descr_type, scheme, csvfile):
	# csv -> sqlite3 table
	con = sqlite3.connect(":memory:")
	con.text_factory = str
	cur = con.cursor()

	qmarks = ",".join(["?"]*len(scheme["cols"]))
	decl = ",".join(["%s %s" % (v, t) for v, t in zip(scheme["cols"], py2sqlite(scheme["types"])) ])
	cols = ",".join([x for x in scheme["cols"]])

	cur.execute("CREATE TABLE t (%s);" % decl)

	with open(csvfile, 'rb') as fin:
	    dr = csv.DictReader(fin, delimiter=";")
	    to_db = [tuple([typ(row[col]) for col, typ in zip(scheme["cols"], scheme["types"])]) for row in dr]

	cur.executemany("INSERT INTO t (%s) VALUES (%s);" % (cols, qmarks), to_db)
	con.commit()

	return cur, con
	# dispose connection

def write_time_interval_file(cur, interval, scheme, out_dir):
	# sql query for filtering time interval
	if "time_end" in scheme:
		print("SELECT * FROM t WHERE %s<'%s' AND '%s'<%s"
			% (interval[0], scheme["time_start"], scheme["time_end"], interval[1]))
		cur.execute("SELECT * FROM t WHERE %s<t.'%s' AND t.'%s'<%s ORDER BY t.'%s'"
			% (interval[0], scheme["time_start"], scheme["time_end"], interval[1], scheme["time_start"]))
		data = cur.fetchall()
		print(data)

if __name__ == "__main__":
	main()

