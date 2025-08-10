/**
 * Routes for PWD-related endpoints
 */
module.exports = (app) => {
    const pwdController = require('../controllers/PWDRecordsController');
    const guardianController = require('../controllers/PWDGuardianController');
    const educationController = require('../controllers/PWDEducationController');
    const supportNeedsController = require('../controllers/PWDSupportNeedsController');
    const authMiddleware = require('../middlewares/auth.middleware');

    // Main PWD Records Routes
    app.get('/api/pwd', authMiddleware.verifyToken, pwdController.index);
    app.post('/api/pwd', authMiddleware.verifyToken, pwdController.create);
    app.get('/api/pwd/status/:status', authMiddleware.verifyToken, pwdController.getByStatus);
    app.get('/api/pwd/community/:community_id', authMiddleware.verifyToken, pwdController.getByCommunity);
    app.get('/api/pwd/search', authMiddleware.verifyToken, pwdController.search);
    app.get('/api/pwd/statistics/:quarter/:year', authMiddleware.verifyToken, pwdController.getStatistics);
    app.get('/api/pwd/:id', authMiddleware.verifyToken, pwdController.show);
    app.put('/api/pwd/:id', authMiddleware.verifyToken, pwdController.update);
    app.patch('/api/pwd/:id/status', authMiddleware.verifyToken, pwdController.updateStatus);
    app.delete('/api/pwd/:id', authMiddleware.verifyToken, pwdController.delete);

    // PWD Guardian Routes
    app.get('/api/guardians/pwd/:pwd_id', authMiddleware.verifyToken, guardianController.getByPWD);
    app.get('/api/guardians/statistics', authMiddleware.verifyToken, guardianController.getStatistics);
    app.get('/api/guardians/:id', authMiddleware.verifyToken, guardianController.show);
    app.post('/api/guardians', authMiddleware.verifyToken, guardianController.create);
    app.put('/api/guardians/:id', authMiddleware.verifyToken, guardianController.update);
    app.delete('/api/guardians/:id', authMiddleware.verifyToken, guardianController.delete);

    // PWD Education Routes
    app.get('/api/education/pwd/:pwd_id', authMiddleware.verifyToken, educationController.getByPWD);
    app.get('/api/education/statistics', authMiddleware.verifyToken, educationController.getStatistics);
    app.get('/api/education/:id', authMiddleware.verifyToken, educationController.show);
    app.post('/api/education', authMiddleware.verifyToken, educationController.create);
    app.put('/api/education/:id', authMiddleware.verifyToken, educationController.update);
    app.delete('/api/education/:id', authMiddleware.verifyToken, educationController.delete);

    // PWD Support Needs Routes
    app.get('/api/support-needs/pwd/:pwd_id', authMiddleware.verifyToken, supportNeedsController.getByPWD);
    app.get('/api/support-needs/statistics', authMiddleware.verifyToken, supportNeedsController.getStatistics);
    app.get('/api/support-needs/:id', authMiddleware.verifyToken, supportNeedsController.show);
    app.post('/api/support-needs', authMiddleware.verifyToken, supportNeedsController.create);
    app.put('/api/support-needs/:id', authMiddleware.verifyToken, supportNeedsController.update);
    app.delete('/api/support-needs/:id', authMiddleware.verifyToken, supportNeedsController.delete);
};
