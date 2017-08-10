Assignment-2b: WordPress-Contributors Plugin
===

Below is how the metabox will appear on the post edit screen.

* **ONLY** users with `capability` as `edit_posts` are displayed in the metabox. 
* If *First Name* and *Last Name* fields are empty then the `user_nicename` will be displayed. *(eg: First name in the list)*
* Checkbox will be `checked` and `disabled` for the post author. *(eg: Second name in the list)*
* User list format modified to `First_Name Last_Name ( user_nicename )`. *(eg: Second & Third name in the list)*

![Metabox](https://s27.postimg.org/s3yot8ug3/mamv21.jpg)

This metabox will be only visible to users with roles:
* administrator
* editor
* author

Below is how the metabox will render on the post page.

![Metaboxoutput](https://s29.postimg.org/u6f91n6pz/metabox_output.png)
