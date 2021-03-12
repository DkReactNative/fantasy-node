module.exports = {
    NodeMailertansport: {
        host: "smtp.gmail.com",
        port: 587,
        secure: false, // true for 465, false for other ports
        auth: {
            user: "email", // generated ethereal user
            pass: "zjpcomprgnhqtraa" // generated ethereal password
        }
    },
    multer: {
        uploadDirectoryPath: {
            doc: "Multer upload directory path",
            format: String,
            default: "../public/uploads/",
        },
    },
    server: {
        maxLoginAttenpts: {
            doc: "To limit invalid login attempt by user",
            format: Number,
            default: 5,
        },
        http: {
            port: {
                doc: "HTTP port to bind",
                format: "port",
                default: 3000,
                env: "PORT",
            },
        },
        https: {
            enableHttpsServer: {
                doc: "Enable https server",
                format: Boolean,
                default: false,
            },
            port: {
                doc: "HTTPS port to bind",
                format: "port",
                default: 3000,
                env: "HTTPSPORT",
            },
            privateKey: {
                doc: "Private key file name",
                format: String,
                default: "key.pem",
            },
            certificate: {
                doc: "Certificate file name",
                format: String,
                default: "cert.crt",
            },
            ca_g1: {
                doc: "Certificate file name",
                format: String,
                default: "g1.crt",
            },
            ca_g2: {
                doc: "Certificate file name",
                format: String,
                default: "g2.crt",
            },
            ca_g3: {
                doc: "Certificate file name",
                format: String,
                default: "g3.crt",
            },
        },
        enableCompression: {
            doc: "Enable HTTP compression",
            format: Boolean,
            default: true,
        },
        enableSessionSQL: {
            doc: "Enable HTTP compression",
            format: Boolean,
            default: false,
        },
        enableStatic: {
            doc: "Enable Express static server",
            format: Boolean,
            default: true,
        },
        enableAuthentication: {
            doc: "Enable Express api authentication",
            format: Boolean,
            default: true,
        },
        enableRequestLogs: {
            doc: "Enable Request Logs",
            format: Boolean,
            default: true,
        },
        static: {
            directory: {
                doc: "Express static server content directory",
                format: String,
                default: "../public/",
            },
            options: {
                doc: "Express static server options",
                format: Object,
                default: { maxAge: 0 },
            },
        },
        security: {
            enableApiKey: {
                doc: "Enable api key authentication",
                format: Boolean,
                default: false,
            },
            enableCORS: {
                doc: "Enable CORS",
                format: Boolean,
                default: true,
            },
            clientApiKey: {
                doc: "Client api ket to access node rest api",
                format: String,
                default: "de8f162b-b9d4-4015-9393-i3hfda7cce7",
            },
            emailSalt: {
                doc: "salt",
                format: String,
                default: "$2a$10$e.oPc.dyrwRoQCpDvO9Rhe",
            },
        },
        CORS: {
            allowedHosts: {
                doc: "Allowed Host for CORS",
                format: Array,
                default: ["http://localhost:3000"],
            },
            allowedMethods: {
                doc: "Allowed HTTP Methods for CORS",
                format: String,
                default: "GET,POST,PUT,PATCH,OPTIONS",
            },
            allowedHeaders: {
                doc: "Allowed HTTP Headers for CORS",
                format: String,
                default: "accept, x-auth-token, content-type, certificate,",
            },
            exposedHeaders: {
                doc: "Exposed HTTP Headers for CORS",
                format: String,
                default: "XSRF-TOKEN",
            },
        },
        session: {
            sidname: {
                doc: "Name of a session",
                format: String,
                default: "connect.sid",
            },
            path: {
                doc: "Path of a session",
                format: String,
                default: "/",
            },
            httpOnly: {
                doc: "httpOnly cookie",
                format: Boolean,
                default: true,
            },
            secure: {
                // should be set to true when using https
                doc: "Http security of a session",
                format: Boolean,
                default: false,
            },
            maxAge: {
                doc: "Maximum age of a session",
                format: Number,
                default: 30 * 24 * 60 * 60 * 1000, // 30 days
            },
            proxy: {
                // should set to true when using https and reverse proxy
                // like HAproxy
                doc: "Http proxy",
                format: Boolean,
                default: false,
            },
            rolling: {
                // should set to true when want to have sliding window
                // session
                doc: "For sliding window of a session",
                format: Boolean,
                default: true,
            },
            cookieSecret: {
                doc: "For sliding window of a session",
                format: String,
                default: "",
            },
        },
        JWT: {
            jwtPrivateKey: {
                doc: "jwt private key",
                format: String,
                default: "demo-secret-key",
            },
            jwtexpiresIn: {
                doc: "jwt expiration time",
                format: String,
                default: "16800h",
            },
        },
        bodyParser: {
            limit: {
                doc: "maximum request body size",
                format: String,
                default: "500mb",
            },
        },
    },
};