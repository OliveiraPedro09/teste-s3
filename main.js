require('dotenv').config();

const express = require('express');
const multer = require('multer');

const {
  S3Client,
  PutObjectCommand,
  GetObjectCommand
} = require('@aws-sdk/client-s3');

const { getSignedUrl } = require('@aws-sdk/s3-request-presigner');

const app = express();
const upload = multer({ dest: 'tmp/' });

// config do cliente S3
const s3 = new S3Client({
  region: process.env.AWS_REGION,
  credentials: {
    accessKeyId: process.env.AWS_ACCESS_KEY_ID,
    secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY
  }
});

const BUCKET = process.env.AWS_BUCKET;

// upload de arquivo teste
app.post('/upload', upload.single('file'), async (req, res) => {
  try {
    const file = req.file;

    const key = `uploads/user/${Date.now()}-${file.originalname}`;

    const command = new PutObjectCommand({
      Bucket: BUCKET,
      Key: key,
      Body: require('fs').readFileSync(file.path),
      ContentType: file.mimetype
    });

    await s3.send(command);

    require('fs').unlinkSync(file.path); // apaga do tmp

    res.json({
      message: 'Upload realizado com sucesso',
      key
    });

  } catch (error) {
    console.error(error);
    res.status(500).json({ error: 'Erro no upload' });
  }
});

// o aws recomenda gerar uma url temporaria
app.get('/file', async (req, res) => {
  try {
    const key = req.query.key;

    if (!key) {
      return res.status(400).json({ error: 'Key obrigatória' });
    }

    const command = new GetObjectCommand({
      Bucket: BUCKET,
      Key: key
    });

    const url = await getSignedUrl(s3, command, {
      expiresIn: 60 * 5 // tempo de vida da url de 5 min
    });

    res.json({ url });

  } catch (error) {
    console.error(error);
    res.status(500).json({ error: 'Erro ao gerar URL' });
  }
});

// teste para verificar se a url temporária funciona, usando um arquivo fixo
app.get('/teste', async (req, res) => {
  try {
    const key = 'uploads/user/teste.txt';

    const command = new GetObjectCommand({
      Bucket: BUCKET,
      Key: key
    });

    const url = await getSignedUrl(s3, command, {
      expiresIn: 300
    });

    res.json({
      file: key,
      url
    });

  } catch (error) {
    console.error(error);
    res.status(500).json({ error: 'Erro no teste' });
  }
});

app.listen(process.env.PORT, () => {
  console.log(`Servidor rodando na porta ${process.env.PORT}`);
});