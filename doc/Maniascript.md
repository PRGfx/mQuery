#Maniascript
###`declareGlobalVariable($dataType, $name)`
 **dataType** *(string)*
The ManiaScript datatype.
 **name** *(string)*
The name for the variable.
###`addFunction($dataType, $name, $content, $parameter="")`
 **dataType** *(string)*
The function's return type as ManiaScript datatype.
 **name** *(string)*
The function's name.
 **content** *(string)*
The function's body.
 **parameter** *(string)*
Optional: Parameters for the function as you would write it for a ManiaScript function.
###*boolean* `isFunction($name)`
 **name** *(string)*
A function's name to check for.
###`declareMainVariable($dataType, $name, $global = false, $content = "", $ifNotExists = false)`
 **dataType** *(string)*
ManiaScript datatype for the variable.
 **name** *(string)*
Name for the variable.
 **global** *(boolean)*
States wether or not the variable should be made global by declaring it for the Page object.
 **content** *(string)*
Optional predefined value for the variable.
 **ifNotExists** *(boolean)*
Able to suppres the Exception.
###`addCodeToMain($code)`
 **code** *(string)*
ManiaScript code to be added.
###`addCodeToLoop($code)`
 **code** *(string)*
ManiaScript code to be added.
###`addKeyPressEvent($controlID, $code)`
 **controlID** *(string)*
The id of the element triggering the event.
 **code** *(string)*
The code executed on triggering the event.
###`addMouseClickEvent($controlID, $code)`
 **controlID** *(string)*
The id of the element triggering the event.
 **code** *(string)*
The code executed on triggering the event.
###`addMouseOverEvent($controlID, $code)`
 **controlID** *(string)*
The id of the element triggering the event.
 **code** *(string)*
The code executed on triggering the event.
###`addMouseOutEvent($controlID, $code)`
 **controlID** *(string)*
The id of the element triggering the event.
 **code** *(string)*
The code executed on triggering the event.
###`addSubEvent($subID, $code, $event)`
###`addCodeAfterMain($code)`
###*mixed* `build($return = true)`
 **return** *(boolean)*
Decide if this function directly outputs the ManiaScript or just returns it.