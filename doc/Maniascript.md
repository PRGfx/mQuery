#Maniascriptby *Blade*  version *03/26/2012*    Handles ManiaScript.  Originally made for Boxes.##Methods----
###`declareGlobalVariable($dataType, $name)`  Declares a variable outside of every function. It will be declared 'for Page'.**Parameters**+ **dataType** *(string)*
The ManiaScript datatype.+ **name** *(string)*
The name for the variable.
###`addFunction($dataType, $name, $content, $parameter="")`  Declares a ManiaScript function.**Parameters**+ **dataType** *(string)*
The function's return type as ManiaScript datatype.+ **name** *(string)*
The function's name.+ **content** *(string)*
The function's body.+ **parameter** *(string)*
Optional: Parameters for the function as you would write it for a ManiaScript function.
###*boolean* `isFunction($name)`  Returns wether a function is already defined.**Parameters**+ **name** *(string)*
A function's name to check for.
###`declareMainVariable($dataType, $name, $global = false, $content = "", $ifNotExists = false)`  Declares a variable in the main() function.**Parameters**+ **dataType** *(string)*
ManiaScript datatype for the variable.+ **name** *(string)*
Name for the variable.+ **global** *(boolean)*
States wether or not the variable should be made global by declaring it for the Page object.+ **content** *(string)*
Optional predefined value for the variable.+ **ifNotExists** *(boolean)*
Able to suppres the Exception.
###`addCodeToMain($code)`  Adds code in the main() function.**Parameters**+ **code** *(string)*
ManiaScript code to be added.
###`addCodeToLoop($code)`  Adds code in the while(true) loop.**Parameters**+ **code** *(string)*
ManiaScript code to be added.
###`addKeyPressEvent($controlID, $code)`  Declares a listener for KeyPress Events.**Parameters**+ **controlID** *(string)*
The id of the element triggering the event.+ **code** *(string)*
The code executed on triggering the event.
###`addMouseClickEvent($controlID, $code)`  Declares a listener for MouseClick Events.**Parameters**+ **controlID** *(string)*
The id of the element triggering the event.+ **code** *(string)*
The code executed on triggering the event.
###`addMouseOverEvent($controlID, $code)`  Declares a listener for MouseOver Events.**Parameters**+ **controlID** *(string)*
The id of the element triggering the event.+ **code** *(string)*
The code executed on triggering the event.
###`addMouseOutEvent($controlID, $code)`  Declares a listener for MouseOut Events.**Parameters**+ **controlID** *(string)*
The id of the element triggering the event.+ **code** *(string)*
The code executed on triggering the event.
###`addSubEvent($subID, $code, $event)`***deprecated***
###`addCodeAfterMain($code)`***deprecated***
###*mixed* `build($return = true)`  Puts everything together.**Parameters**+ **return** *(boolean)*
Decide if this function directly outputs the ManiaScript or just returns it.