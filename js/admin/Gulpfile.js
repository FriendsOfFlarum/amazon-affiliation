const gulp = require('flarum-gulp');

gulp({
    modules: {
        'flagrow/amazon-affiliation': [
            'src/**/*.js',
        ],
    },
});
