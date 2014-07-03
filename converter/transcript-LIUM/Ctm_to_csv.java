import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;

public class Ctm_to_csv  {

	public static String tokenize(String untokenedSource, String separator){
		String[] tokenized = untokenedSource.split(separator);
		StringBuilder builder = new StringBuilder();
		for(String s : tokenized) {
			builder.append(s+";");
		}
		return builder.toString();
	}

	public static void main(String [] args) throws Throwable {
		File folder = null;
		File output_folder = null;
		if(args.length>0){
			System.out.println("INPUT DIRECTORY provided: "+args[0]);
			folder= new File(args[0]+"//transcripts//LIUM//1-best//");
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

			File[] files = folder.listFiles();

			if(files.length!=0)
				for(int index=0;index<files.length;++index){
					file= new File(files[index].toString());
					if(files[index].toString().endsWith(".ctm")){
						ctmFiles++;
						String inputName=file.getName().split(".ctm")[0];
					System.out.println("File"+index+": "+inputName);
					BufferedReader br = null;
					BufferedWriter bw = null;

					try {
						String currentLine;
						br = new BufferedReader(new FileReader(file.toString()));
						bw = new BufferedWriter(new FileWriter(output_folder+"//"+inputName+".transcript-lium.csv"));
						bw.write("Filename;SDR(1);Start Time;Duration Time;Word;Confidence\n");
						while((currentLine=br.readLine())!= null){

							String list=tokenize(currentLine, " ");
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
				}
				else
					System.out.println("!! File" +index+": "+file.getName()+" is NOT ctm file - extension:"+file.getName().substring(file.getName().lastIndexOf(".") + 1));
			}
		else{
			System.out.println("There's no data in "+ folder+"!\nprogram quits");
		}
	System.out.println("\n---------------------------------------------------"
					  +"\n"+ctmFiles+" files were converted succesfully!");
	}
}
