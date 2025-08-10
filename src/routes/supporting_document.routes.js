/**
 * Routes for supporting documents
 */
module.exports = (app) => {
    const documentController = require('../controllers/SupportingDocumentController');
    const authMiddleware = require('../middlewares/auth.middleware');

    // Supporting Document Routes
    app.get('/api/documents', authMiddleware.verifyToken, documentController.index);
    app.get('/api/documents/entity/:entity_type/:entity_id', authMiddleware.verifyToken, documentController.getByEntity);
    app.get('/api/documents/:id', authMiddleware.verifyToken, documentController.show);
    app.post('/api/documents/upload', authMiddleware.verifyToken, documentController.upload);
    app.get('/api/documents/:id/download', authMiddleware.verifyToken, documentController.download);
    app.delete('/api/documents/:id', authMiddleware.verifyToken, documentController.delete);
};
