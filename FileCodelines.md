# Get codelines from selected file 

on click -> get file name and path > create item in notemate_framefiles table (C:\Users\emmah\Herd\ahoy_studio1\database\migrations\2025_09_14_214625_create_notemate_framefiles_table.php)
(think of framefile as file and framefolder as folder)
-> get each line of code from the file and add an item to the codelines table


notemate_framefiles
id	bigint unsigned Auto Increment	
framefile_name	varchar(255)	
framefile_path	varchar(255)	
framefile_extension	varchar(255) NULL	
is_framefolder	tinyint(1)	
parent_id	bigint unsigned NULL	
created_at	timestamp NULL	
updated_at	timestamp NULL

notemate_codelines
Column	Type	Comment
id	bigint unsigned Auto Increment	
codeline	varchar(255)	
comment	varchar(255)	
language	varchar(255)	
created_at	timestamp NULL	
updated_at	timestamp NULL