import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

public class Ctm_to_csv  {

	/**
	 * testdata 
	 */
	private static String input="inputName";

	/**
	 * testdata extension
	 * 
	 */
	private static String fileExtension=".csv";

	/**
	 * tokenizing separator
	 * default: space
	 */
	public static String separator=" ";

	static List<File> files=  new ArrayList<File>();

	/**
	 * @return the input
	 */
	public static String getInput() {
		return input;
	}

	/**
	 * @param set the input
	 */
	public static void setInput(String input) {
		Ctm_to_csv.input = input;
	}

	/**
	 * Splitting the source
	 * 
	 * @param untokenedSource - source file's line
	 * @param separator - space
	 * @return separated string
	 */
	public static String tokenize(String untokenedSource, String separator){
		String[] tokenized = untokenedSource.split(separator);
		StringBuilder builder = new StringBuilder();
		for(String s : tokenized) {
			builder.append(s+",");
		}
		return builder.toString();
	}
	
	/**
	 * List only the files from dir-subdirs
	 * @param directory	- the given directory (with full path)
	 * @return - all the files
	 */
	public static List<File> listFiles(File directory){
		File[] fList = directory.listFiles();
		for (File file : fList){
			if (file.isFile()){
				files.add(file);
			} 
			else if (file.isDirectory()){
				listFiles(file);
			}
		}
		return files;
	}

	public static void main(String [] args) throws Throwable {
		File folder = null;
		File output_folder = null;
		if(args.length>0){
			System.out.println("INPUT DIRECTORY provided: "+args[0]);
			folder= new File(args[0]);
		}
		else{
			System.out.println("NO INPUT/OUTPUT DIRECTORY provided!\narg0 - Input directory\narg1 - Output directory");
			return;
		}
		if(args.length>1){
			System.out.println("OUTPUT DIRECTORY provided: "+args[1]+"\n");
			output_folder= new File(args[1]);
		}
		else{
			System.out.println("OUTPUT DIRECTORY is not provided!");
			return;
		}
				
		output_folder.mkdir();
		int ctmFiles=0;
		File file= null;

		List<File> files=listFiles(folder);

		if(!files.isEmpty())
			for(int index=0;index<files.size();++index){
				file= new File(files.get(index).toString()); 
				if(files.get(index).toString().endsWith(".ctm")){
					ctmFiles++;
					setInput(file.toString());
					String inputName=file.getName().split(".ctm")[0]; 	//kiterjeszt�s lev�g�s
					System.out.println("File"+index+": "+inputName);
					BufferedReader br = null;
					BufferedWriter bw = null;

					try {
						String currentLine;
						br = new BufferedReader(new FileReader(getInput()));
						bw = new BufferedWriter(new FileWriter(output_folder+"//"+inputName+fileExtension));
						bw.write("Filename,SDR(1),Start Time,Duration Time,Word,Confidence\n");
						while((currentLine=br.readLine())!= null){

							String list=tokenize(currentLine, separator);
							bw.write(list);
							bw.write("\n");
						}
						} catch (IOException e) {
							e.printStackTrace();
						} finally {
							try {
								if (br != null)br.close();
								if (bw != null)bw.close();
							} catch (IOException ex) {
								ex.printStackTrace();
							}
						}
					System.out.println("\tConverted OUTPUT:\t "+output_folder+"\\"+inputName+fileExtension);
				}
				else
					System.out.println("!! File" +index+": "+file.getName()+" is NOT ctm file - extension:"+file.getName().substring(file.getName().lastIndexOf(".") + 1));
			}
		else{
			System.out.println("There's no data in "+ folder+"!\nprogram quits");
		}
	System.out.println("\n---------------------------------------------------"
					  +"\n"+ctmFiles+"/"+files.size()+" files were converted succesfully!");
	}
}
