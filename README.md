#Page List Plus

Page List Plus expands the options available to MojoMotor's native page_list tag
Version: 1.0
Author: Aaron Fowler (http://twitter.com/adfowler)
License: Apache License v2.0


##Usage

{mojo:pagelistplus:page_list}
Without any parameters this outputs a navigation menu identical to MojoMotor's {mojo:site:page_list} tag.


##Parameters:

class/id // same as default mojomotor page_list

The "start" parameter tells the tag from where to start building the menu.

Start at the current page
	start="current"

Start at the current page's immediate parent. If the current page is top level, outputs nothing.
	start="parent"

Start at the current page's root parent. If the current page is top level, start with itself (same as start="current")
start="root"

Output the page title of the page specified with the start parameter, wrapped in an HTML tag
	header="h1/h2/p/div/etc..."

If a page list is output prepend/append some text or HTML
prepend="some text or HTML"
append="some text or HTML"


##Example

	{mojo:pagelistplus:page_list start="parent" header="h2" prepend="<div class='navlinks'>" append="</div>"}

Outputs:

	<div class='navlinks'>
		<h2>Parent Page Title</h2>
		<ul>
			<li>...
		</ul>
	</div>