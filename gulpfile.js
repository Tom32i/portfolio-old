var gulp       = require('gulp'),
    concat     = require('gulp-concat'),
    uglify     = require('gulp-uglify'),
    header     = require('gulp-header'),
    jshint     = require('gulp-jshint'),
    sourcemaps = require('gulp-sourcemaps'),
    plumber    = require('gulp-plumber'),
    sass       = require('gulp-sass'),
    minify     = require('gulp-minify-css'),
    prefixer   = require('gulp-autoprefixer'),
    shell      = require('gulp-shell'),
    meta       = require('./package.json'),
    onError    = function (error) { console.log(error.toString()); this.emit('end'); };

var assetsDir = './src/Resources/',
    srcDir    = './src/',
    distDir   = './dist/',
    publicDir = './src/Resources/public',
    vendors   = [
        './node_modules/moment/min/moment.min.js'
    ],
    banner    = [
      '/*!',
      ' * <%= name %> <%= version %>',
      ' * Copyright Thomas Jarrand 2015',
      ' */\n\n'
    ].join('\n');

gulp.task('js-hint', function() {
    return gulp.src(assetsDir + 'js/**/*.js')
        .pipe(plumber({ errorHandler: onError }))
        .pipe(jshint())
        .pipe(jshint.reporter());
});

gulp.task('js-full', function() {
    return gulp.src(vendors.concat([assetsDir + 'js/**/*.js']))
        .pipe(plumber({ errorHandler: onError }))
        .pipe(sourcemaps.init())
        .pipe(concat(meta.name + '.js'))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(publicDir + '/js'));
});

gulp.task('js-min', function() {
    return gulp.src(vendors.concat([assetsDir + 'js/**/*.js']))
        .pipe(plumber({ errorHandler: onError }))
        .pipe(concat(meta.name + '.js'))
        .pipe(uglify())
        .pipe(header(banner, meta))
        .pipe(gulp.dest(publicDir + '/js'));
});

gulp.task('css', function() {
    return gulp.src(assetsDir + 'css/*.scss')
        .pipe(plumber({ errorHandler: onError }))
        .pipe(sass())
        .pipe(prefixer())
        .pipe(minify())
        .pipe(gulp.dest(publicDir + '/css'));
});

gulp.task('html', shell.task([
    'bin/console phpillip:build thomas.jarrand.fr',
]));

gulp.task('watch', ['dev'], function () {
    gulp.watch(assetsDir + 'js/**/*.js', ['js-hint', 'js-full']);
    gulp.watch(assetsDir + 'css/**/*.scss', ['css']);
    gulp.watch([srcDir + '**/*'], ['html']);
});

gulp.task('dev', ['css', 'js-full', 'html']);
gulp.task('default', ['css', 'js-min', 'html']);
