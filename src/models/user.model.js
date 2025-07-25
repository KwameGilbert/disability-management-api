const db = require('../config/db');

const getAllUsers = async () => {
  const result = await db.query('SELECT * FROM users ORDER BY created_at DESC');
  return result.rows;
};

const createUser = async ({ name, email, password }) => {
  const result = await db.query(
    'INSERT INTO users (name, email, password) VALUES ($1, $2, $3) RETURNING *',
    [name, email, password]
  );
  return result.rows[0];
};

module.exports = {
  getAllUsers,
  createUser,
};
