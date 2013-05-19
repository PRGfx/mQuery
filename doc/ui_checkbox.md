ui_checkbox()
====
Requires
----
`<entry name="nameInForm" id="entryIdent" posn="x y z" />`  
Note that the entry itself won't be displayed lateron. The checkbox will be created at the position of the entry. You can access the value of the checkbox by the defined entry name.

Attributes
----
+ **values**  takes a javascript-like array, e.g. `["active", "inactive"]`
+ **checked** True or false, determining the checkbox'es state on load
+ **function** *unique!* name for a function to manually check or uncheck the box via `[functionName](True);` or False respectively.
+ **classChecked** The name for the class formatting the look of he checked checkbox.
+ **classUnchecked** The name for the class formatting the look of he unchecked checkbox.
+ **onChange** Something like `function(data){ log(data); }`. Will be fired on un/checking the checkbox, handling the respective checkbox values as the variable you pass as parameter for function. You can't use return statements here!