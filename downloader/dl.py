import wget, os, sys

if len(sys.argv) < 5:
    print("usage %s <user> <password> <src file> <dst dir>" % sys.argv[0])
    sys.exit(-1)

user = sys.argv[1]
passw = sys.argv[2]
src_file = sys.argv[3]
dst = sys.argv[4]

# path the file that lists the base names of the videos
list_name = src_file 
# base url the database resides
base_url = "https://%s:%s@beehub.nl/home/alyr/cAXES/dev" % (user, passw)
# list of (path, postfix, prefix) tuples to specify the subdirectories the dataset was split into
# path is relative to the base url, postfix is actually the extension of the file
sub_dirs = [("json", "json", "v"),
    ("ProsodicFeatures", "wav.opensmile.csv", ""),
    ("subtitles", "xml", "v"),
    ("tar", "tar", "v"),
    ("transcripts/LIMSI", "xml", "v"),
    ("transcripts/LIUM/1-best", "ctm", ""),
    ("webdata/cast", "txt", ""),
    ("wav", "wav", "v"),
    ("webm", "webm", "v")]
# directory the dataset will be downloaded to
out = dst

base_names = open(list_name).read().splitlines()

for name in base_names:
    for path, postfix,prefix in sub_dirs:
        real_name = name[1:]
        url = "%s/%s/%s%s.%s" % (base_url, path, prefix, real_name, postfix)
        dst_dir = "%s/%s/" % (out, path)
        if not os.path.exists(dst_dir):
                os.makedirs(dst_dir)
        print("")
        print(url)
        dlfile = wget.download(url)
        dst = "%s/%s%s.%s" % (dst_dir, prefix, real_name, postfix)
        os.rename(dlfile, dst)
