clock()
====
An automatically refreshing clock in a label.

Requires
----
A selectable label (with id or class).

Attributes
----
+ **format** A string from special letters like the [php date-function](http://www.php.net/manual/de/function.date.php), available modes are listed below.

Available date types
----
**H**  -  Hours, 24-hour format
**h**  -  Hours, 12-hour format
**A**  -  ***AM*** or ***PM***
**a**  -  ***am*** or ***pm***
**i**  -  Minutes
**s**  -  Seconds

**Y**  -  Year with 4 characters, e.g. ***2013***
**y**  -  Year with 2 characters, e.g. ***13***
**m**  -  Month with leading zeros, e.g. ***08***
**F**  -  Month as word, ***January*** to ***December***
**M**  -  Month as substring, ***Jan*** to ***Dec***
**d**  -  Day with leading zeros, e.g. ***05***
**S**  -  ***st***, ***nd***, ***rd*** or ***th*** matching the day of the month

Example
----
```javascript
$("#clockLabel").clock({format: "m/d/Y h:i:s a"});
```