const path = require('path');

module.exports = {
  mode: 'production',
  entry: './src/emedia_library_picker.js',
  output: {
    filename: 'emedia_library_picker.bundle.js',
    path: path.resolve(__dirname, 'dist'),
    libraryTarget: 'umd',
    library: 'EMediaLibraryPicker',
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [ '@babel/preset-env' ],
          }
        }
      },
      {
        test: /\.css$/i,
        use: ['style-loader', 'css-loader'],
      }
    ]
  },
  // Remove externals entirely
  // externals: {}
};
