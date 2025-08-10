/**
 * Routes for assistance management
 */
module.exports = (app) => {
    const assistanceController = require('../controllers/AssistanceController');
    const authMiddleware = require('../middlewares/auth.middleware');

    // Assistance Routes
    app.get('/api/assistance', authMiddleware.verifyToken, assistanceController.index);
    app.get('/api/assistance/status/:status', authMiddleware.verifyToken, assistanceController.getByStatus);
    app.get('/api/assistance/pwd/:pwd_id', authMiddleware.verifyToken, assistanceController.getByPWD);
    app.get('/api/assistance/quarter/:quarter/:year', authMiddleware.verifyToken, assistanceController.getByQuarter);
    app.get('/api/assistance/statistics', authMiddleware.verifyToken, assistanceController.getStatistics);
    app.get('/api/assistance/:id', authMiddleware.verifyToken, assistanceController.show);
    app.post('/api/assistance', authMiddleware.verifyToken, assistanceController.create);
    app.put('/api/assistance/:id', authMiddleware.verifyToken, assistanceController.update);
    app.patch('/api/assistance/:id/status', authMiddleware.verifyToken, assistanceController.updateStatus);
    app.delete('/api/assistance/:id', authMiddleware.verifyToken, assistanceController.delete);
};
