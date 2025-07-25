const db = require('../config/db');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');

const generateToken = (user) => {
    return jwt.sign({ id: user.id, role: user.role }, process.env.JWT_SECRET, {
        expiresIn: process.env.JWT_EXPIRY,
    });
};

exports.register = async (req, res) => {
    const { full_name, email, password, role } = req.body;

    const hashedPassword = await bcrypt.hash(password, 10);
    const existing = await db.query('SELECT * FROM users WHERE email = $1', [email]);

    if (existing.rows.length > 0) return res.status(409).json({ message: 'User already exists' });

    const result = await db.query(
        'INSERT INTO users (full_name, email, password, role) VALUES ($1, $2, $3, $4) RETURNING id, full_name, email, role',
        [full_name, email, hashedPassword, role]
    );

    const user = result.rows[0];
    const token = generateToken(user);

    res.status(201).json({ user, token });
};

exports.login = async (req, res) => {
    const { email, password } = req.body;

    const result = await db.query('SELECT * FROM users WHERE email = $1', [email]);
    const user = result.rows[0];

    if (!user) return res.status(401).json({ message: 'User not found' });

    const valid = await bcrypt.compare(password, user.password);
    if (!valid) return res.status(401).json({ message: 'Wrong Password' });

    delete user.password;
    const token = generateToken(user);

    res.json({ user, token });
};

exports.me = async (req, res) => {
    const result = await db.query('SELECT id, full_name, email, role FROM users WHERE id = $1', [req.user.id]);
    res.json(result.rows[0]);
};
