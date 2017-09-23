# Syntax Highlighter
Integrates Alex Gorbatchevs Syntax highlighter into CKEditor.

## Libraries
This module is basically just a Drupal 8 wrapper for these two libraries:

### CKEDITOR plugin:
http://ckeditor.com/addon/syntaxhighlight

### Theming:
https://github.com/syntaxhighlighter/syntaxhighlighter


## Setup
Edit the text format which you are using.
Add the "Syntax Highlighter" icon to the toolbar. Make sure the filter "Limit allowed HTML tags" is disabled.
You have to disable that filter, because the Pluing will generate invalid HTML for the class attribute of the inserted &lt;pre&gt; element,
and with the filter enabled, Drupal will remove some of the HTML generated.

## TODO
There's probably a bunch of stuff you could do to make this better. These two just pops into my mind:
* Remove the libraries from the module
