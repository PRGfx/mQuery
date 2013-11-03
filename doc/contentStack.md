contentStack()
====
Multiple elements will be replaced one after another in cycles.

Attributes
----
+ **group** A name to group elements to one contentStack.  
+ **play** Boolean value on whether it will automatically start going through the stack.
+ **function** Name of a function you want to use with "next", "prev" or "[id]"
+ **ticks** How many milliseconds it needs until it automatically shows up the next element.
+ **onChange** Something like `function(data){...}`, called when the stack changes the displayed element, giving the new display index as paramenter.

Example
----
```javascript
$(".myContainer").contentStack({group: "myStack1", play: false, function: "myStack1Fn"});
$("#rotateMyStack").click(function(){
	myStack1Fn("next");
});
```