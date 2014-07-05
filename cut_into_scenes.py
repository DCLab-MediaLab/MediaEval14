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
				"time_start": "begin",
				"time_end": "end"},
			"transcript-lium":
				{"cols": ['filename', 'sdr', 'start', 'duration', 'word', 'confidence'],
				"time_start": "start",
				"length": "duration"}

			#"json":	['diskref','service','date','time',
			#	'duration','uri','canonical','depiction',
			#	'title','description','original_description',
			#	'subtitles_uri','when','id','filename',
			#	'source','service_name']
			}
	if descr_type not in switch:
		print("warn: can not handle descr_type %s" % descr_type)
	else:
		process_by_scheme(switch[descr_type], filename)
		write_time_interval_file()

def process_by_scheme(scheme, csvfile):
	con = sqlite3.connect(":memory:")
	cur = con.cursor()
	cols = ",".join([i.replace(" ", "") for i in scheme])
	qmarks = ",".join(["?"]*len(scheme))
	print(cols)
	cur.execute("CREATE TABLE t (%s);" % cols)

	with open(csvfile, 'rb') as fin:
	    dr = csv.DictReader(fin, delimiter=";")
	    to_db = [tuple([i[j] for j in scheme]) for i in dr]

	print(to_db[1])

	cur.executemany("INSERT INTO t (%s) VALUES (%s);" % (cols, qmarks), to_db)
	con.commit()

	return cur
	# dispose connection


def write_time_interval_file(cur, interval, scheme, out_dir):
	cur.execute("SELECT * FROM t WHERE ")

if __name__ == "__main__":
	main()

