=== Rails Theme ===
Contributors: paulrosen
Donate link: http://www.performantsoftware.com/wordpress/plugins/rails_theme/
Tags: rails, theme
Requires at least: 3.2
Tested up to: 3.5
Stable tag: 1.1.1.0

This calls back to a Rails (or other) web service to get theme information, so that it is easy to keep the rails and WP sides in sync.

== Description ==

This calls back to a web service to get theme information, so that it is easy to keep the rails and WP sides in sync.

It is designed to be used when a Rails application wishes to contain WordPress content. This allows the WordPress pages
to directly use the styles of the Rails application so that it will always match, even when the Rails app changes.

It is also useful when the header of the page changes, for instance, if new menu items can appear depending on the state of the
application.

And finally, if you want to POST back to the Rails app (for instance, to log in), you need the CSRF to be set in the page's header,
and that needs to come from the Rails session.

NOTE: This might take some tweaking depending on the theme you are using. It successfully works with the default 2011 theme
and the Hybrid theme.

It requires an entry point in your Rails controller that returns three things:
1. a section that is put in the header to load stylesheets and javascript,
1. a section that is loaded at the top of the body, and
1. a section that is loaded at the bottom of the body.

There is one main option in the plugin to tell it the base URL of the Rails app. The rails app is expected to respond to the
following URL:

`/wrapper`

This also supplies a convenience option to add classes to the <body> so that you can match the body styles in your rails app.

== Installation ==

The plugin itself is easy to install.

1. Upload this folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Click 'Settings' in the dashboard, then 'Rails Theme' and enter the base URL for your Rails app.

There are a number of things that have to happen on the Rails side, though.

You will need something like the following in your routes.rb file:

`get "/wrapper" => "home#wrapper" `

And it should return a partial with three sections. The sections should be separated from each other with 10 tildes (that
is: `~~~~~~~~~~`).

It is most convenient to rearrange your layout file to call those three partials. Here is a possible layout file:

`<!DOCTYPE html>
<html> 
<head>
    <%= render :partial => '/layouts/dependencies' %>
    <title>Site Title</title> 
</head>
<body> 
   <%= render :partial => '/layouts/header' %>
    <%= yield %>
    <%= render :partial => '/layouts/footer' %>
 </body> </html>`

Notice that all the work is done by those three partials.

Then, the wrapper call is handled like this:

* home_controller:
`def wrapper
   render :partial => "/layouts/wrapper"
end`

* layouts/_wrapper.html.erb:
`<%= render :partial => "/layouts/dependencies" %>
<%= render :partial => "/layouts/any_session_related_tasks %>
~~~~~~~~~~
<%= render :partial => "/layouts/header" %>
~~~~~~~~~~
<%= render :partial => "/layouts/footer" %>`

You will have to be careful about the css classes you create so that they don't conflict with the WP theme's classes.
For instance, you probably don't want a class named 'content'.

You will also probably have to tweak the css in your layout some to override some of your theme. In the case of the
2011 theme, I had to put in the following:

`/* for wordpress */
#body-container {
    margin: 0;
}
#header-container {
   display:none;
}`

The big problem with making this seamless is that WP can't use the session data from the Rails app. Typically in
my apps, this means that I don't know how to draw the "sign in" section because I can't tell if the user is logged in.
To get around that, the sign in section should be drawn as if there is no one logged in, then an ajax call made from
the Rails app to correct that. That is what should go in the `any_session_related_tasks` partial above: some javascript
that triggers at `onload` time that returns the sign in div.

== Frequently Asked Questions ==

= How do I get rails to ignore the WP's PHP? =

If you are using Passenger and Apache, put this in your conf file (assuming you want the WordPress site to be accessible
through `/news`):

`<Directory "/path/to/rails/app/public/news">
    PassengerEnabled off
    AllowOverride all
</Directory>`

= How do I get the URL for the WP blog look like the URLs for my Rails app? =

If you create a symbolic link in your public folder to the wp site, then you can use the URL /news to get to your blog:

`cd path/to/rails/app/public
ln -s /path/to/wordpress/installation news`

== Troubleshooting ==

1. My theme doesn't show up at all.

Turn on WP_DEBUG in wp_config.php and see if there is an error message printed to the page.

== Screenshots ==

1. This is the options page.

== Changelog ==

= 1.1.1.0 =
* Retry the call to rails, since the rails app or the network might be slow.

= 1.1.0.0 =
* Added the option to add classes to <body>.

= 1.0.3.0 =
* Removing the pass through of cookies, since later versions of Rails invalidates the session when it sees the cookie
coming from the wrong place.

= 1.0.2.0 =
* Using WP_Http to make the server call to work in more configurations.
* Improved error reporting.

= 1.0.1.0 =
* Pass the page's cookies through to the rails app.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1.0 =
* This version is needed if your rails app depends on cookies to generate the correct header.

= 1.0 =
* Initial release.
