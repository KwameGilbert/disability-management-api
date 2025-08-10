/**
 * Routes for role management
 */
module.exports = (app) => {
    const rolesController = require('../controllers/RolesController');
    const authMiddleware = require('../middlewares/auth.middleware');

    // Roles Routes
    app.get('/api/roles', authMiddleware.verifyToken, rolesController.index);
    app.get('/api/roles/permissions', authMiddleware.verifyToken, rolesController.getAllPermissions);
    app.get('/api/roles/:id', authMiddleware.verifyToken, rolesController.show);
    app.get('/api/roles/:id/permissions', authMiddleware.verifyToken, rolesController.getPermissions);
    app.post('/api/roles', authMiddleware.verifyToken, authMiddleware.isAdmin, rolesController.create);
    app.put('/api/roles/:id', authMiddleware.verifyToken, authMiddleware.isAdmin, rolesController.update);
    app.post('/api/roles/:id/permissions', authMiddleware.verifyToken, authMiddleware.isAdmin, rolesController.assignPermissions);
    app.delete('/api/roles/:id', authMiddleware.verifyToken, authMiddleware.isAdmin, rolesController.delete);
};
