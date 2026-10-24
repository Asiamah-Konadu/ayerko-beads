try {
  module.exports = require('../server');
} catch (error) {
  console.error('Failed to initialize the application:', error);
  module.exports = (_req, res) => {
    res.statusCode = 500;
    res.setHeader('content-type', 'text/plain; charset=utf-8');
    res.end('Application startup failed. Check the function logs for details.');
  };
}
