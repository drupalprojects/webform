Installation
------------

### Installing the Webform Module

1. Copy/upload the webform module to the modules directory of your Drupal
   installation.

2. Enable the 'Webform' module and desired [sub-modules](#sub-modules) in 'Extend'. 
   (/admin/modules)

3. Set up user permissions. (/admin/people/permissions#module-webform)

4. Build a new webform (/admin/structure/webform)
   or duplicate an existing template (/admin/structure/webform/templates).
   
5. Publish your webform as a:

    - **Page:** By linking to the published webform.
      (/webform/contact)  

    - **Node:** By creating a new node that references the webform.
      (/node/add/webform)

    - **Block:** By placing a Webform block on your site.
      (/admin/structure/block)

6. (optional) Install [third party libraries](#third-party-libraries).

7. (optional) Install [additional contrib modules](#additional-contrib-modules).


### Sub Modules

**Webform UI**

The Webform UI module provides a simple user interface for building and 
maintaining webforms.

> Unless your website is maintained by experienced Drupal developers, 
> every website should enable the Webform UI module. 

**Webform Templates**

The Webform Templates module provides starter templates that can be used 
to create new webforms. 

> Besides using the provided default starter templates, you can also create 
> custom templates for your organization.

**Webform Node**

The Webform Node module provides a 'Webform' content type, which allows  
forms to be integrated into a website as nodes.

> The Webform Node module creates a webform (entity reference) field
> that allows any webform to be attached to any content type.  

**Webform Examples**

The Webform Examples module provides examples of all available webform elements 
and functionality. The Webform Examples module can used for demonstrating
and testing advanced functionality or used as cut-n-paste code snippets
for creating new webforms.

> The Webform Examples module allows site builders and developers to 
> preview and experiment with working examples of all supported webform elements 
> and features.

### Additional Contrib Modules 

When installed, the modules below will enhance your website's webform building and 
submission handling functionality and experience.

**[Webform Migrate](https://www.drupal.org/sandbox/dippers/2819169)**

The [Webform Migrate](https://www.drupal.org/sandbox/dippers/2819169) provides 
a migration source plugin and a migration template to migrate Drupal 6
webforms to Drupal 8 Webforms.

**[Webform Queue](https://www.drupal.org/project/webform_queue)**

The [Webform Queue](https://www.drupal.org/project/webform_queue) module 
provides a queue handler for Webform, to store webform submissions in a queue.

**[Token](https://www.drupal.org/project/token)**

The [Token](https://www.drupal.org/project/token) module provides additional 
tokens not supported by core (most notably fields), as well as a UI for browsing 
tokens.

> Tokens are supported and actively used by the Webform module. Installing the
> Token module will provide webform builders with the ability to browse webform and 
> submission specific tokens. 

**[Mail System](https://www.drupal.org/project/mailsystem) and [Swift Mailer](https://www.drupal.org/project/swiftmailer)**

The [Mail System](https://www.drupal.org/project/mailsystem) module provides an 
Administrative UI and Developers API for managing the mail backend/plugin.
 
The [Swift Mailer](https://www.drupal.org/project/swiftmailer) extends the 
basic e-mail sending functionality provided by Drupal by delegating all e-mail
handling to the Swift Mailer library.

> The Webform module provide support for HTML email. However to send file
> attachments, please install and configure the 
> [Mail System](https://www.drupal.org/project/mailsystem) and 
> [Swift Mailer](https://www.drupal.org/project/swiftmailer) modules.

**[Honeypot](https://www.drupal.org/project/honeypot)**

The [Honeypot](https://www.drupal.org/project/honeypot) module uses both the 
honeypot and timestamp methods of deterring spam bots from completing webforms on 
your Drupal site.

> The Honeypot module provides the best unobtrusive protection against SPAM webform submissions.

**[CAPTCHA](https://www.drupal.org/project/captcha) and [reCAPTCHA](https://www.drupal.org/project/recaptcha)**

The [CAPTCHA](https://www.drupal.org/project/captcha) module provides the 
CAPTCHA API for adding challenges to arbitrary webforms.

The [reCAPTCHA](https://www.drupal.org/project/recaptcha) module uses the
[Google reCAPTCHA](https://www.google.com/recaptcha/intro/index.html) web 
service to improve the CAPTCHA system, and to protect email addresses.

> CAPTCHA provides additional, slightly obtrusive protection against SPAM 
> submissions.

**[Clientside Validation](https://www.drupal.org/project/clientside_validation)

The [Clientside Validation](https://www.drupal.org/project/clientside_validation) 
module adds clientside validation (aka "Ajax webform validation") for all webforms 
using [jquery.validate](https://jqueryvalidation.org/).

> The Clientside validation provides cross-browser inline validation message.

**[Validators](https://www.drupal.org/project/validators)**

The [Validators](https://www.drupal.org/project/validators) module allows you 
to use the Symfony Validator component within a webform.

> The Validators module is one of the Webform module's supported 
> validation mechanisms.

### Third Party Libraries

The Webform module utilizes the third-party Open Source libraries listed below
to enhance webform elements and to provide additional functionality. It is recommended
that these libraries be installed in your Drupal installations /libraries 
directory. If these libraries are not installed, they are automatically loaded
from a CDN.

> PLEASE NOTE: The 
> [Libraries API](https://www.drupal.org/project/libraries) for Drupal 8 is 
> still under development.  

Currently the best way to download all the needed third party libraries is to 
either add [webform.libraries.make.yml](http://cgit.drupalcode.org/webform/tree/webform.libraries.make.yml)
to your drush make file or execute the below drush command from the root of your
Drupal installation.  

    drush webform-libraries-download    

**[Code Mirror](http://codemirror.net/)** - [Demo](http://codemirror.net/)

A versatile text editor implemented in JavaScript for the browser.

> Code Mirror is used to provide a text editor for YAML and HTML configuration
> settings and messages.

**[Geocomplete](https://ubilabs.github.io/geocomplete/)** - [Demo](http://ubilabs.github.io/geocomplete/examples/form.html)

An advanced jQuery plugin that wraps the Google Maps API's Geocoding and Places Autocomplete services.

> Geocomplete is used by the location element.

**[Input Mask](http://robinherbots.github.io/jquery.inputmask/)** - [Demo](http://robinherbots.github.io/jquery.inputmask/)

Input masks ensures a predefined format is entered. This can be useful for 
dates, numerics, phone numbers, etc...

> Input masks are used to ensure predefined and custom formats for text fields.

**[RateIt](https://github.com/gjunge/rateit.js)** - [Demo](http://gjunge.github.io/rateit.js/examples/)

Rating plugin for jQuery. Fast, progressive enhancement, touch support,
customizable (just swap out the images, or change some CSS), unobtrusive
JavaScript (using HTML5 data-* attributes), RTL support. The Rating plugin
supports as many stars as you'd like, and also any step size.

> RateIt is used to provide a customizable rating webform element.

**[Select2](https://select2.github.io/)** - [Demo](https://select2.github.io/examples.html)

Select2 gives you a customizable select box with support for searching and 
tagging.

> Select2 is used to improve the user experience for select menus.

**[Signature Pad](https://github.com/szimek/signature_pad)** - [Demo](http://szimek.github.io/signature_pad/)

Signature Pad is a JavaScript library for drawing smooth signatures.
It is HTML5 canvas based and uses variable width Bézier curve interpolation.
It works in all modern desktop and mobile browsers and doesn't depend on any 
external libraries.

> Signature Pad is used to provide a signature element.

**[Timepicker](https://github.com/jonthornton/jquery-timepicker)** - [Demo](http://jonthornton.github.io/jquery-timepicker/)

A lightweight, customizable javascript timepicker plugin for jQuery, inspired by Google Calendar.

> Timepicker is used to provide a polyfill for HTML 5 time elements.

**[Toggles](https://github.com/simontabor/jquery-toggles)** - [Demo](https://simontabor.com/labs/toggles/)

Toggles is a lightweight jQuery plugin that creates easy-to-style toggle buttons.

> Toggles is used to provide a toggle element.

**[Word and Character Counter](https://github.com/qwertypants/jQuery-Word-and-Character-Counter-Plugin)** - [Demo](http://qwertypants.github.io/jQuery-Word-and-Character-Counter-Plugin/)

The jQuery word and character counter plug-in allows you to count characters
or words.

> Word or character counting, with server-side validation, is available for text 
> fields and text areas.

**[CKEditor](http://ckeditor.com/)**

The standard version of the CKEditor.

> Allows the Webform module to implement a basic and simpler CKEditor.
