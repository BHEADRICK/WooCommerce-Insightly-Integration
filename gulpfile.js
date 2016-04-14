var gulp = require('gulp');
var Path = require('path');

gulp.task('deploy', function () {

  var zip = require('gulp-zip');
  var findParentDir = require('find-parent-dir');
  var config = {
      accessKeyId: "ACCESSKEY",
      secretAccessKey: "secretAccessKey"
  }
   var s3 = require('gulp-s3-upload')(config);
  var dir;
  try {

    dir = findParentDir.sync(__dirname, '.git');

    dir = dir.split('/').pop();

  } catch(err) {
    console.error('error', err);
  }
  return gulp.src([ '**' ,'!.*', '!composer.json', '!package.json', '!gulpfile.js', '!node_modules/**' ])
    .pipe(zip(dir+'.zip'))
    .pipe(gulp.dest('../')).pipe(s3({
            Bucket: 'bucket-name', //  Required
        }, {
            // S3 Construcor Options, ie:
            maxRetries: 5
        }));
});

gulp.task('rename', function(){

  var replace = require('gulp-replace');
  var rename = require("gulp-rename");
// If a name has been passed...
  if(process.argv[4] !==undefined){

    var fullName = process.argv[4];
    var className = fullName.replace(/ /g, '');
    var slug = fullName.toLowerCase().replace(/ /g, '-').replace(/---/g, '-');
    var hookSlug = fullName.toLowerCase().replace(/ /g, '_');
console.log({fullname:fullName, classname:className, slug: slug, hookslug:hookSlug})
    gulp.src(['plugin-name.php'])
    .pipe(replace(/My Plugin Name/g, fullName))
    .pipe(replace(/plugin_name/g, hookSlug))
    .pipe(replace(/PluginName/g, className))
    .pipe(replace(/plugin-name/g, slug))
      .pipe(rename({

        basename:slug}))
      .pipe(gulp.dest("./"));
  }

});

gulp.task('move', function(){
  // If a name has been passed...
    if(process.argv[4] !==undefined){

      var fullName = process.argv[4];
      var className = fullName.replace(/ /g, '');
      var slug = fullName.toLowerCase().replace(/ /g, '-').replace(/---/g, '-');
      var hookSlug = fullName.toLowerCase().replace(/ /g, '_');
  var findParentDir = require('find-parent-dir');
  var fs = require('fs');
        var fulldir = findParentDir.sync(__dirname, '.git');

        var basedir = fulldir.split('/').pop();
        var newdir = fulldir.replace(basedir, slug);
        fs.rename(fulldir, newdir);
      }
})
