const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const sequelize = require('./config/database');
const User = require('./models/User');
const Trip = require('./models/Trip');

const app = express();
const port = 3306;

// Middleware
app.use(cors());
app.use(bodyParser.json());

// Synchronisation des modèles avec la base de données
sequelize.sync()
  .then(() => {
    console.log('Les tables ont été créées!');
  })
  .catch((error) => {
    console.error('Impossible de créer les tables:', error);
  });
// Modèle de trajet
const tripSchema = new sequelize.Schema({
  departure: String,
  destination: String,
  date: String,
  seatsAvailable: Number,
});

const Trip = sequelize.model('Trip', tripSchema);
// Modèle d'utilisateur
const userSchema = new sequelize.Schema({
  username: { type: String, required: true, unique: true },
  password: { type: String, required: true }
});

const User = sequelize.model('User', userSchema);
// Route pour l'inscription
app.post('/api/register', async (req, res) => {
  try {
    const { username, password } = req.body;

    // Hacher le mot de passe
    const hashedPassword = await bcrypt.hash(password, 10);

    const newUser = new User({
      username,
      password: hashedPassword
    });

    await newUser.save();
    res.status(201).send('Utilisateur créé');
  } catch (err) {
    res.status(400).send(err);
  }
});
// Route pour la connexion
app.post('/api/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    const user = await User.findOne({ username });

    if (!user) return res.status(404).send('Utilisateur non trouvé');

    // Comparer le mot de passe avec celui haché
    const validPassword = await bcrypt.compare(password, user.password);

    if (!validPassword) return res.status(400).send('Mot de passe incorrect');

    // Générer un JWT
    const token = jwt.sign({ id: user._id }, 'secretkey', { expiresIn: '1h' });

    res.json({ token });
  } catch (err) {
    res.status(400).send(err);
  }
});

// Route pour récupérer les trajets
app.get('/api/trips', async (req, res) => {
  try {
    const trips = await Trip.find();
    res.json(trips);
  } catch (err) {
    res.status(500).send(err);
  }
});

// Route pour ajouter un trajet
app.post('/api/trips', async (req, res) => {
  try {
    const newTrip = new Trip(req.body);
    await newTrip.save();
    res.status(201).send(newTrip);
  } catch (err) {
    res.status(400).send(err);
  }
});

// Démarrer le serveur
app.listen(port, () => {
  console.log(`Serveur backend démarré sur http://localhost:${port}`);
});