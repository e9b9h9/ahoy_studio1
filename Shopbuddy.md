# Shopbuddy Development Plan
Shopbuddy is the name of the Terminal Like interface for sending http commands with words like a cli terminal

## Examples of Commands to Add
terminal on notemate page always sends the entered text with notemate prepended to it

so the terminal would look like this:
notemate > 

 new file-watcher start  : sends back "Enter Project CodeFolder Path:"
 file-watcher start : sends back a numbered list of codefolders 
 file-watcher stop 
 feature *any text that follows* (everything else blank) : (requirements, there is a codefolder with is_working = true, otherwise it fails with "no open codefolder") .. Adds text as the feature to the notemate_memory table, makes is_working = true 
cd feature
notemate feature > the same thing as feature *any text that follows*, but without having to type "feature" again
notemate feature > update-comments (runs the update-comments logic )

## Implementation Ideas
Backend Structure
1. ShopbuddyParser.php (main parser for Shopbuddy)
2. NotemateCommands.php (specific notemate logic)

## Front end additions Shopbuddy.vue (with state management)
Notemate page, should have Shopbuddy say "notemate >" this would prepend notemate to all the commands entered 
If I cd ./ it would just be > with no prefix 
or if I cd notemate somewhere else, it would prepend notemate somewhere else to the entered commands

### Shopbuddy-dispatcher.js
Sends some commands back to the front end and some to the back 

Examples: 
Shopbuddy
notemate > new file-watcher start

new file-watcher start requires a folder path

Shopbuddy 
notemate new file-watcher start > "entered/file/path" 
On enter- sends to dispatcher
dispatcher sends to ShopbuddyParser

### ShopbuddyParser.php (service)


# Shopbuddy Development Plan
Shopbuddy is the name of the Terminal Like interface for sending http commands with words like a cli terminal

## Examples of Commands to Add
terminal on notemate page always sends the entered text with notemate prepended to it

so the terminal would look like this:
notemate > 

 new file-watcher start  : sends back "Enter Project CodeFolder Path:"
 file-watcher start : sends back a numbered list of codefolders 
 file-watcher stop 
 feature *any text that follows* (everything else blank) : (requirements, there is a codefolder with is_working = true, otherwise it fails with "no open codefolder") .. Adds text as the feature to the notemate_memory table, makes is_working = true 
cd feature
notemate feature > the same thing as feature *any text that follows*, but without having to type "feature" again
notemate feature > update-comments (runs the update-comments logic )

## Implementation Ideas
Backend Structure
1. ShopbuddyParser.php (main parser for Shopbuddy)
2. NotemateCommands.php (specific notemate logic)

## Front end additions Shopbuddy.vue (with state management)
Notemate page, should have Shopbuddy say "notemate >" this would prepend notemate to all the commands entered 
If I cd ./ it would just be > with no prefix 
or if I cd notemate somewhere else, it would prepend notemate somewhere else to the entered commands

### Shopbuddy-dispatcher.js
Sends some commands back to the front end and some to the back 

Examples: 
Shopbuddy
notemate > new file-watcher start

new file-watcher start requires a folder path

Shopbuddy 
notemate new file-watcher start > "entered/file/path" 
On enter- sends to dispatcher
dispatcher sends to ShopbuddyParser

### ShopbuddyParser.php (service)


