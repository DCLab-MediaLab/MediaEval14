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
		for descr in csvfiles:
			descr_type = os.path.split(descr)[-1].split(".")[1:-1]
			descr_type = ".".join(descr_type)
			process_type(descr_type, descr)

def process_type(descr_type, filename):
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
				"length": "duration"}
			}
	if descr_type not in switch:
		print("warn: can not handle descr_type %s" % descr_type)
	else:
		cur = process_by_scheme(switch[descr_type], filename)
		write_time_interval_file(cur, (30.0, 40.), switch[descr_type], "outt")

def py2sqlite(tplst):
	tpdict = {float: "REAL", str: "TEXT", int: "INTEGER"}
	return [tpdict[i] for i in tplst]

def process_by_scheme(scheme, csvfile):
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

	print(to_db[0])

	return cur
	# dispose connection

def write_time_interval_file(cur, interval, scheme, out_dir):
	if "time_end" in scheme:
		print("SELECT * FROM t WHERE %s<'%s' AND '%s'<%s"
			% (interval[0], scheme["time_start"], scheme["time_end"], interval[1]))
		cur.execute("SELECT * FROM t WHERE %s<t.'%s' AND t.'%s'<%s ORDER BY t.'%s'"
			% (interval[0], scheme["time_start"], scheme["time_end"], interval[1], scheme["time_start"]))
		data = cur.fetchall()
		print(data)

if __name__ == "__main__":
	main()

