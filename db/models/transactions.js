const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('transactions', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    order_id: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    txn_id: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    banktxn_id: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    txn_date: {
      type: DataTypes.DATE,
      allowNull: true
    },
    txn_amount: {
      type: DataTypes.FLOAT,
      allowNull: true
    },
    currency: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    gateway_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    checksum: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    local_txn_id: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    added_type: {
      type: DataTypes.TINYINT,
      allowNull: true
    },
    status: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1,
      comment: "0=>Pending,1=>success"
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'transactions',
    timestamps: false
  });
};
