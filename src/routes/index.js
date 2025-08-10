/**
 * Index file for all routes
 */
module.exports = (app) => {
    // Include all route files
    require('./auth.routes')(app);
    require('./user.routes')(app);
    require('./role.routes')(app);
    require('./pwd.routes')(app);
    require('./assistance.routes')(app);
    require('./supporting_document.routes')(app);

    // Default route
    app.get('/', (req, res) => {
        res.json({ message: 'Welcome to Disability Management API' });
    });
};
