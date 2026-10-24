try {
  module.exports = require('../server');
} catch (error) {
  console.error('Failed to initialize the application:', error);
  const details = error instanceof Error
    ? `${error.name}: ${error.message}`.replace(/:\/\/[^/@\s]+@/g, '://[redacted]@')
    : 'Unknown startup error';
  module.exports = (_req, res) => {
    res.statusCode = 500;
    res.setHeader('content-type', 'text/plain; charset=utf-8');
    res.end(`Application startup failed: ${details}`);
  };
}
