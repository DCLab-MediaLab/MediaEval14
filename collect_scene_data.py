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

def process_by_scheme(scheme, csvfile):
	con = sqlite3.connect(":memory:")
	cur = con.cursor()
	cur.execute("CREATE TABLE t (c1, c2, c3, c4, c5, c6);")

	with open('../out/20080401_002000_bbcthree_pulling.transcript-lium.csv','rb') as fin: # `with` statement available in 2.5+
	    # csv.DictReader uses first line in file for column headings by default
	    dr = csv.DictReader(fin) # comma is default delimiter
	    to_db = [(i['Filename'], i['SDR(1)'], i['Start Time'], i['Duration Time'], i['Word'], i['Confidence']) for i in dr]

	cur.executemany("INSERT INTO t (c1, c2, c3, c4, c5, c6) VALUES (?, ?);", to_db)
	con.commit()	

def process_transcript_lium():
	pass

if __name__ == "__main__":
	main()

