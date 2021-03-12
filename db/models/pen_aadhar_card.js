const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('pen_aadhar_card', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    pan_card: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    pan_image: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    pan_name: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    date_of_birth: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    state: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    aadhar_card: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    is_verified: {
      type: DataTypes.TINYINT,
      allowNull: true,
      comment: "0=>not verified,1=>verified"
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'pen_aadhar_card',
    timestamps: false,
    indexes: [
      {
        name: "PRIMARY",
        unique: true,
        using: "BTREE",
        fields: [
          { name: "id" },
        ]
      },
      {
        name: "user_id",
        using: "BTREE",
        fields: [
          { name: "user_id" },
        ]
      },
    ]
  });
};
