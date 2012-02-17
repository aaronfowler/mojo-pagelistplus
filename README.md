#Page List Plus

Page List Plus expands the options available to MojoMotor's native page_list tag

Version: 1.3.0

Authors: 
- Aaron Fowler (http://twitter.com/adfowler)
- Gerhard Dalenoort (http://twitter.com/GDmac)

License: OSL3

##Installation

Install into a "pagelistplus" folder inside MojoMotor's /system/mojomotor/third-party folder.

##Usage

	{mojo:pagelistplus:page_list}

This outputs a navigation menu, almost identical to MojoMotor's {mojo:site:page_list} tag.

- every li item has a CSS class "mojo_page_list_[url_title]"
- every active page has a class "mojo_active"
- every active parent page has a class "parent_active"


##Parameters

pagelistplus accepts the default mojomotor page_list parameters  
page, depth, class, id, plus some extra

###start=
The "start" parameter tells the tag from where to start building the menu.  
There are several options to choose from

start="current"  
Starts from the current page.

start="parent"  
Start at the current page's immediate parent. If the current page is top level, outputs nothing.

start="root"  
Starts at the current page's root parent. Shows all the children of the topmost page.  

###header=
Output the page title of the page specified in the start or page parameter,  
wrapped in a HTML tag. header="h1/h2/p/div/etc..."

###header_link="yes"
If set to "yes", the header tag will also output a link in the header

##prepend="some text or HTML" append="some text or HTML"
If a page list is output prepend/append some custom text or HTML


##Example

###{mojo:pagelistplus:page_list start="root" depth="1" id="top_nav"}

Outputs:

	<ul id="top_nav">
		<li class="mojo_page_list_page1 mojo_active"><a href="http://example.com/index.php/welcome">Welcome</a></li>
		<li class="mojo_page_list_page2"><a href="http://example.com/index.php/about">About</a></li>
	</ul>


###{mojo:pagelistplus:page_list start="root" header_link="no" header="h3" id="side_nav"}

Outputs:

	<h3>Welcome</h3>
	<ul id="side_nav">
		<li class="mojo_page_list_ut1"><a href="http://example.com/index.php/ut1">some_page1</a></li>
		<li class="mojo_page_list_ut2"><a href="http://example.com/index.php/ut2">some_page2</a></li>
		<li class="mojo_page_list_ut3 parent_active"><a href="http://example.com/index.php/ut3">some_page3</a>
			<ul>
				<li class="mojo_page_list_ut4 mojo_active"><a href="http://example.com/index.php/ut4">some_page4</a></li>
				<li class="mojo_page_list_ut5"><a href="http://example.com/index.php/ut5">some_page5</a></li>
			</ul>
		</li>
	</ul>

