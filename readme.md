mQuery - Framework for Manialinks
====
What it is
----
mQuery included the possiblitiy of formating your ManiaPlanet Manialink with attributes like you are used to from css. Usualy these are the normal Manialink element's attributes, but there are even more possibilities automating even more.
The other point in mQuery is the jQuery like mQuery language giving you huge functionality in very few commands, saving you a lot of time and worries.

How it works
----
The framework preprocesses your input, excludes unnecessary parts that were only used to implement your mQuery. It automatically adds necessary Manialink elements and adds the css formatings according to id- and classnotations.

How to use it
----
First of all you need to catch your manialink, for example with
```php
<?php  
ob_start();  
include('manialink.xml');  
$MLData = ob_get_contents();    
ob_end_clean();  
$MLDoc = new ManialinkAnalysis\ManialinkAnalizer($MLData);  // Preprocess the manialink  
$MLDoc->applyStyles();										// Apply the css styles to the manialink elements  
$MLDoc->output();											// Output formatted manialink 
?>
```
  
In the manialink you can declare stylesheet files with `<style href="*[filename.css]*" />`.
mQuery code files can be included with `<script src="*[scriptfile.whatever]*" />` or written within `<mquery></mquery>`blocks.

Version
----
0.4 alpha
