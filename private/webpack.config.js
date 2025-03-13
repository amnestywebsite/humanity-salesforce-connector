const path = require('path');
const webpack = require('webpack');
const ESLintPlugin = require('eslint-webpack-plugin');
const { EsbuildPlugin } = require('esbuild-loader');

// Project paths.
const SRC_PATH = 'src/';
const OUT_PATH = '../assets';

/**
 * Get the cache configuration for the build mode
 * @param {String} mode the build mode
 * @returns {Object|False}
 */
const getCacheConf = (mode) => {
  if (mode === 'production') {
    return false;
  }

  return {
    type: 'filesystem',
    profile: false,
    buildDependencies: {
      config: [__filename],
    },
  };
};

const config = (env, argv) => ({
  bail: argv.mode === 'production',
  cache: getCacheConf(argv.mode),
  target: 'web',
  profile: false,
  devtool: argv.mode === 'production' ? 'source-map' : 'eval',
  entry: {
    app: path.resolve(__dirname, `${SRC_PATH}/app.js`),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, OUT_PATH),
    pathinfo: false,
  },
  externals: {
    lodash: 'lodash',
    react: 'React',
    'react-dom': 'ReactDOM',
  },
  watchOptions: {
    ignored: /node_modules/,
  },
  performance: {
    hints: false,
  },
  optimization: {
    minimize: argv.mode === 'production',
    minimizer: [
      new EsbuildPlugin({
        target: 'es2015',
        css: true,
      }),
    ],
  },
  resolve: {
    symlinks: false,
  },
  stats: {
    all: false,
    assets: true,
    errors: true,
    errorDetails: true,
    excludeAssets: [/\.(eot|ttf|woff2?|jpg|png|svg)$/],
  },
  plugins: [
    new ESLintPlugin(),
    // Sets mode so we can access it in `postcss.config.js`.
    new webpack.LoaderOptionsPlugin({ options: { mode: argv.mode } }),
  ],
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        exclude: /(lodash|node_modules|react(-dom)?)/,
        loader: 'esbuild-loader',
        options: {
          target: 'es2015',
        },
      },
    ],
  },
});

module.exports = (env, argv) => config(env, argv);
