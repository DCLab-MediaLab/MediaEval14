import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class Nst {

	public static File input_folder = null;
	public static File output_folder = null;
	
	public static void main(String[] args) {
		try {
			if(args.length>0){
				System.out.println("INPUT DIRECTORY provided: "+args[0]);
				input_folder= new File(args[0]+"//transcripts//NST-Sheffield//");
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
			int xmlFiles=0;
			File file= null;

			File[] files = input_folder.listFiles();

			if(files.length!=0)
				for(int index=0;index<files.length;++index){
					file= new File(files[index].toString());
					if(files[index].toString().endsWith(".xml")){
						xmlFiles++;
						String inputName=file.getName().split(".xml")[0].substring(1);
						System.out.println("File"+index+": "+inputName);
						
						DocumentBuilder dBuilder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
						Document doc = dBuilder.parse(file);
						
						if (doc.hasChildNodes()) {
							printNote(doc.getChildNodes());
						}
						BufferedWriter bw = null;
						bw = new BufferedWriter(new FileWriter(output_folder+"//"+inputName+".transcript-NST.csv"));
											
						bw.write("segment_end;segment_speakerid;segment_start;word_end;word_start;word\n");

						for(Object i : values)
							bw.write(i.toString());
						bw.close();
					}
					values.clear();
				}
			System.out.println("\nSuccesfully converted "+ xmlFiles+" files!");
		} catch (Exception e) {
			System.out.println(e.getMessage());
		}
	}

	private static List<Object> values=new ArrayList<Object>();	
	private static boolean segment = false; //copy-flag for segment
	
	private static void printNote(NodeList nodeList) {

		for (int count = 0; count < nodeList.getLength(); count++) {
			Node tempNode = nodeList.item(count);

			// make sure it's element node.
			if (tempNode.getNodeType() == Node.ELEMENT_NODE) { 
				
				// copy the segment attributes untill the next segment
				if(segment==true && Arrays.asList("word").contains(tempNode.getNodeName())){
					values.add(values.get(values.size()-6));
					values.add(values.get(values.size()-6));
					values.add(values.get(values.size()-6));
				}
				
				// for empty semgents leave out word and attributes
				if(segment==false && Arrays.asList("segment").contains(tempNode.getNodeName()) && !values.isEmpty())
						values.add("\n");
			

				if (tempNode.hasAttributes()) {
					
					// get attributes names and values
					NamedNodeMap nodeMap = tempNode.getAttributes();

					for (int i = 0; i < nodeMap.getLength(); i++) {
						Node node = nodeMap.item(i);
						if(!Arrays.asList("module","webasr").contains(tempNode.getNodeName())){
							values.add(node.getTextContent()+";");
						}
					}
				}
				
				if( Arrays.asList("word").contains(tempNode.getNodeName())){
					values.add(tempNode.getTextContent()+"\n");
					segment=true;
				}
				else segment=false;

				if (tempNode.hasChildNodes()) {
					// loop again if has child nodes
					printNote(tempNode.getChildNodes());
				}
			}
			
		}
	}
}
