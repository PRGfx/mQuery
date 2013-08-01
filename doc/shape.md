&lt;shape /&gt;
====
The `<shape />` tag will create a basic shape according to the given parameters.    
As it will mostly behave like any other manialink element, it will take    
`posn, sizen, url, action, manialink, scriptevents, id` and `class`.  
The colour of the shape will be given just like for quads with    
+ bgcolor="rgba" (where the opacity is optional) (it also takes a 6+2 hexadecimal format)  
The maybe most interesting attributes are these:  
+ type="rectangle|circle|arc"  
+ filled="true|false" just display a border or a filled shape?  
+ weight=".." sets the weight of the border in case `filled` is false  

options for `type="arc"`:  
+ angle="0-360" Angle for an arch in degrees  
+ rotation="0-360" Rotation angle to rotate an arch  
  
Direct ManiaScript-support is not planned yet.
