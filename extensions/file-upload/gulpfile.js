'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass');
const uglify = require('gulp-uglify');
const rename = require("gulp-rename");
const cssmin = require('gulp-cssmin');
const browserify = require('browserify');
const streamify = require('gulp-streamify');
const sourcemaps = require('gulp-sourcemaps');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');

gulp.task('css', function () {
    let files = './assets/src/sass/[^_]*.scss';

    return gulp.src(files)
        // create .css file
        .pipe(sass())
        .pipe(rename({ extname: '.css' }))
        .pipe(gulp.dest('./assets/dist/css/'));
});

function js (file) {
    const filename = file.split('/').pop()
    return browserify({ entries: [file] })
        .transform('babelify', {
            presets: [
                ['@babel/preset-env', { targets: '> 0.25%, not dead', forceAllTransforms: true }]
            ],
            plugins: [
                ['@babel/plugin-proposal-decorators', { legacy: true }],
                ['@babel/plugin-transform-react-jsx', { pragma: 'h' }]
            ]
        })
        .bundle()
        .pipe(source(filename))
        .pipe(buffer())
        .pipe(gulp.dest('./assets/dist/js'))
}
gulp.task('js', () => js('assets/src/js/admin.js'));

gulp.task('minify-js', gulp.series('js', function() {
    return gulp.src(['./assets/src/js/**/*.js'])
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(streamify(uglify()))
        .pipe(rename({extname: '.min.js'}))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./assets/dist/js/'));
}));

gulp.task('minify-css', gulp.series('css', function() {
    return gulp.src(["./assets/src/css/*.css"])
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(cssmin({ sourceMap: true }))
        .pipe(rename({extname: '.min.css'}))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest("./assets/dist/css/"));
}));

gulp.task('default', gulp.series('css', 'js', 'minify-css', 'minify-js'));

gulp.task('watch', function () {
    gulp.watch('./assets/src/sass/**/*.scss', gulp.series('css'));
    gulp.watch('./assets/src/js/**/*.js', gulp.series('js'));
});
