#Page List Plus

Page List Plus expands the options available to MojoMotor's native page_list tag

Version: 1.2.0

Author: Aaron Fowler (http://twitter.com/adfowler)

License: Apache License v2.0


##Installation

Install into a "pagelistplus" folder inside MojoMotor's /system/mojomotor/third-party folder.

##Usage

	{mojo:pagelistplus:page_list}

Without any parameters this outputs a navigation menu identical to MojoMotor's {mojo:site:page_list} tag.


##Parameters

Same as default mojomotor page_list
	class / id / page


The "start" parameter tells the tag from where to start building the menu.

Start at the current page
	start="current"


Start at the current page's immediate parent. If the current page is top level, outputs nothing.
	start="parent"


Start at the current page's root parent. If the current page is top level, start with itself (same as start="current")
	start="root"


Output the page title of the page specified with the start or page parameter, wrapped in an HTML tag
	header="h1/h2/p/div/etc..."


If "yes", output a link to the page title of the page specified with the start or page parameter
	header_link="yes"


If a page list is output prepend/append some text or HTML
	prepend="some text or HTML"
	append="some text or HTML"


##Example

	{mojo:pagelistplus:page_list start="parent" id="parentnav" header="h2" header_link="yes" prepend="<div class='navlinks'>" append="</div>"}

Outputs:

	<div class="navlinks">
		<h2><a href="http://example.com/page/parent_page">Parent Page Title</a></h2>
		<ul id="parentnav">
			<li>...
		</ul>
	</div>