// webpack.config.js
var path = require('path');
var webpack = require('webpack');

var pathToScripts = 'site/profiles/kk/themes/kaatheme/scripts';

module.exports = {
  devtool: 'source-map',
  debug: true,
  entry: './' + pathToScripts + '/scripts.js',
  output: {
    path: path.join(__dirname, pathToScripts),
    filename: 'scripts.min.js'
  },
  plugins: [
    new webpack.DefinePlugin({
      'process.env': {
        'NODE_ENV': JSON.stringify('development')
      }
    })
  ],
  module: {
    preLoaders: [
      {
        test: /\.jsx?$/,
        loader: "eslint-loader",
        include: path.join(__dirname, pathToScripts),
        exclude: path.join(__dirname, pathToScripts + '/scripts.min.js'),
      }
    ],
    loaders: [
      {
        test: /\.jsx?$/,
        loaders: ['babel', 'babel?presets[]=es2015,presets[]=stage-0,plugins[]=transform-runtime'],
        include: path.join(__dirname, pathToScripts),
        exclude: path.join(__dirname, pathToScripts + '/scripts.min.js'),
      }
    ]
  },
  eslint: {
    configFile: './.eslintrc'
  }
};