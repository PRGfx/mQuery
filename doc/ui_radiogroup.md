ui_radiogroup()
====
Requires
----
`<quad value="radioButtonValue" class="radiogroup1" posn="x y z" />`  
Give a (or multiple quads) at the designated position. It usually is formatted with a stylesheet. Another quad to display a *selected* state will be generated. A entry to make the construction usable in a form will be generated.

Attributes
----
+ **function** *unique!* name for a function to manually select a radio-button with `[functionName](id)`
+ **classChecked** The name for the class formatting the look of he checked radio-button.
+ **classUnchecked** The name for the class formatting the look of he unchecked radio-button.
+ **onChange** Something like `function(data){ log(data); }`. Will be fired on selecting a radio-button, handling it's respective value as the variable you pass as parameter for function. You can't use return statements here!
+ **group** A string that should be the same for every radio-button in the group.
+ **name** A string determining which name is set for the generated entry, so you will be able to access it by this name in a form.