Mouseactions
====
click()
----
Code will be executed on clicking one of the matched elements.
```javascript
$("#clickMe").click(function(){
	log("You clicked me :$");
	// do something
});
```

mouseover()
----
Code will be executed when moving the mouse over one of the matched elements.

mouseout()
----
Code will be executed when moving the mouse out of one of the matched elements after it was over it.

hover(in, out)
----
Code *in* will be executed on mouseover, code *out* is optional. If given, it will be executed on mouseout (see above).


Examples
----
```javascript
$(".clickable").mouseover(function(){
	$this.PosnX -= 10;
}).click(function(){
	$this.PosnX = 50;
	log("Position reset.");
});
```
The above example will be triggered by multiple elements (if found) with a class *"clickable"*. Moving the mouse over it, will cause the object to go left by 10. Clicking the element will reset the element's x-position to 50 and write something on the console.

```javascript
global var infobox = $("#infobox");
$("#infoTrigger").hover(function(){
	infobox.Show();
}, function(){
	infobox.Hide();
});
```
Let's say, there is some box with additional info, like some kind of tooltip, hidden by default. Now there might be some icon with the id *"infoTrigger"*. Once you move your mouse onto that icon, the info-box will be displayed. When you move your cursor away, the box will be hidden again.