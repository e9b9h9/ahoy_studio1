# Table: codeblocks
Column	Type	Comment
id	bigint unsigned Auto Increment	
codeline	varchar(255)	
comment	varchar(255) NULL	
parent_codeline	bigint unsigned NULL	
linked_codelines	json	
codeblock_id	bigint unsigned	
created_at	timestamp NULL	
updated_at	timestamp NULL

Foreign keys
Source	Target	ON DELETE	ON UPDATE	
codeblock_id	codeblocks(id)	RESTRICT	RESTRICT	Alter
parent_codeline	codeblocks(id)	RESTRICT	RESTRICT	Alter

## What are codeblocks? 
All the codelines that are required to make some logical feature work 

Example:
import ShopbuddyOutput from './ShopbuddyOutput.vue';
import { computed } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: ''
  }
});
const currentPrefix = computed(() => store.getInitialPrefix(props.id));
<ShopbuddyOutput :instance-id="props.id" />



# Table: variables
Column	Type	Comment
id	bigint unsigned Auto Increment	
variable	varchar(255)	
transformations	json	
type	varchar(255)	
variable_id	bigint unsigned	
created_at	timestamp NULL	
updated_at	timestamp NULL	

## What are variables? 
Variables in codelines, transformations are all the ways the variable gets transformed across codelines

# Table: master_codelines
Column	Type	Comment
id	bigint unsigned Auto Increment	
codeline	text	
variables	json	
purpose_key	varchar(255)	
file_location	varchar(255)	
language_id	bigint unsigned NULL	
created_at	timestamp NULL	
updated_at	timestamp NULL	

Foreign keys
Source	Target	ON DELETE	ON UPDATE	
language_id	languages(id)	CASCADE	RESTRICT	Alter

## What is master_codelines?
This is for a quick reference for new codelines, each unique line of code gets added here

# Table: notemate_framefiles
Column	Type	Comment
id	bigint unsigned Auto Increment	
framefile_name	varchar(255)	
framefile_path	varchar(255)	
framefile_extension	varchar(255) NULL	
is_framefolder	tinyint(1)	
parent_id	bigint unsigned NULL	
created_at	timestamp NULL	
updated_at	timestamp NULL

Foreign keys
Source	Target	ON DELETE	ON UPDATE	
parent_id	notemate_framefiles(id)	CASCADE	RESTRICT	Alter

## What are framefiles? 
These are files/folders used as copies/not live editing files. 

