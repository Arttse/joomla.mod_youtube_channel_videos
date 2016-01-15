var mod         = 'mod_youtube_channel_videos',
    fs          = require ( 'fs' ),
    manifestXML = fs.readFileSync ( './' + mod + '/' + mod + '.xml', 'utf8' ),
    xml2json    = require ( 'xml2json' ),
    manifest    = (JSON.parse ( xml2json.toJson ( manifestXML ) )).extension,
    gulp        = require ( 'gulp' ),
    zip         = require ( 'gulp-zip' );

gulp.task ( 'create.installer', function () {
    return gulp
        .src ( './' + mod + '/**' )
        .pipe ( zip ( mod + '__' + manifest.version[1] + '__installer.zip' ) )
        .pipe ( gulp.dest ( './.installers' ) );
} );