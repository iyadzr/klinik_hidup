module.exports = {
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'json', 'vue'],
  transform: {
    '^.+\\.vue$': 'vue-jest',
    '^.+\\.js$': 'babel-jest'
  },
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/assets/js/$1'
  },
  testMatch: [
    '**/__tests__/**/*.spec.[jt]s?(x)'
  ],
  transformIgnorePatterns: [
    '/node_modules/(?!axios).+\\.js$'
  ]
};
