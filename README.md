Plugin Boilerplate
==========================

This is a basic scaffold for a new WordPress plugin based on the generator found
at http://wppb.me/

There is also a gulp action to zip the build files (excluding the dev files), name the zip file as the enclosing folder name, and upload it to an S3 bucket

##Requirements

npm and gulp should be installed globally

##Installation

After unzipping or cloning, run the following in Terminal (Mac/Linux) or gitbash (Windows):

npm update

Then you can run the following to name the plugin:

gulp rename --name="Name of the Plugin"

To also rename the containing folder, you'll also need to run:

gulp move --name="Name of the Plugin"

##Deploy

To zip up the plugin and upload it to an S3 bucket:

Update the gulpfile with your s3 creds and bucket name, and then run:

gulp deploy

(The zipped file will be located in the plugins directory)
