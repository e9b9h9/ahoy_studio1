
# Development Plan

## Shopbuddy commands


## Laravel File Watcher


## Create Tables / Models 
Create notemate_codefolders: id, path to folder, is_working (boolean)
NotemateProjectCodeFolder model

## Add command 
 "file-watcher start new"
parse command 
sends back "Enter Project CodeFolder Path:"
adds codefolder path to notemate_project_codefolders table, makes  is_working = true 
sends http request to the chokidar filewatcher for the specified path 

## Create Tables/Models 
notemate_memory: feature (this would be the feature currently working), feature_starttime, files that have been changed (json, list of files/folders saved since feature had been started basically backup memory incase browser loses the info), is_working (true for the most current, when a new feature starts, all the other features should be set to 0), codefolder  

## Add feature command
"text entered" (everything else blank)
parse command (requirements, there is a codefolder with is_working = true, otherwise it fails with "no open codefolder")
Adds text as the feature to the notemate_memory table, makes is_working = true 

## front End - Updates 
Create front end to show files/folder changes that are happening in the working codefolder

## Comments
When the changed file/folder is clicked, the file contents should open in the center div "main content area" as a table with each codeline as a row and on the right column the "feature" as a comment 
multiple comments open at a time

## Create Tables/Models 
notemate_project_codefolder_files: codefolder_id (foreign key), filename, file_path, is_folder
notemate_project_codefolder_features table: id, name, description, parent id (nullable)
notemate_project_codefolder_files_features: file id, feature id 
notemate_codefolder_feature_contents: id, feature (foreign key), codeline, comment, file_name  

NotemateProjectCodefolderFile
NotemateProjectCodefolderFeature
NotemateProjectCodefolderFileFeature
NotemateProjectCodefolderContent

## 12. Add Comments Command 
Shopbuddy > update comments
at the Shopbuddy terminal "update comments" should add the comments to each file that is open
change is_working to false at the notemate_memory table 
Create an item in the notemate_project_codefolder_files table for each file/folder added with comments 
Create an item in the notemate_project_codefolder_features table for the feature entered 
Create and item in the notemate_project_codefolder_files_features table for each file and associate it with the new feature
Create an item in the notemate_codefolder_feature_contents table for each codeline in each file that was edited 