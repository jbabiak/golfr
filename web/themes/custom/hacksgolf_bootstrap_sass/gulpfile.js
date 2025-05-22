const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');
const cleanCss = require('gulp-clean-css');
const rename = require('gulp-rename');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const postcssInlineSvg = require('postcss-inline-svg');
const pxtorem = require('postcss-pxtorem');

const postcssProcessors = [
  postcssInlineSvg({
    removeFill: true,
    paths: ['./node_modules/bootstrap-icons/icons']
  }),
  pxtorem({
    propList: ['font', 'font-size', 'line-height', 'letter-spacing', '*margin*', '*padding*'],
    mediaQuery: true
  })
];

const paths = {
  scss: {
    src: './scss/style.scss',
    dest: './css',
    watch: './scss/**/*.scss',
    bootstrap: './node_modules/bootstrap/scss/bootstrap.scss'
  }
};

function styles() {
  return gulp.src([paths.scss.bootstrap, paths.scss.src])
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: [
        './node_modules/bootstrap/scss',
        '../../contrib/bootstrap_barrio/scss'
      ]
    }).on('error', sass.logError))
    .pipe(postcss(postcssProcessors))
    .pipe(postcss([autoprefixer()]))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(cleanCss())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.scss.dest));
}

// 👇 Watch task
function watchFiles() {
  gulp.watch(paths.scss.watch, styles);
}

exports.styles = styles;
exports.watch = watchFiles;

// 👇 Default now compiles and then watches
exports.default = gulp.series(styles, watchFiles);
