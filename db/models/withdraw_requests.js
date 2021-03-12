const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('withdraw_requests', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    amount: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    refund_amount: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    request_status: {
      type: DataTypes.TINYINT,
      allowNull: true,
      comment: "0=>pending,1=>confirm,2=>cancel"
    },
    email: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    type: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    click_confirm: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    click_confirm_time: {
      type: DataTypes.DATE,
      allowNull: false
    },
    is_lock: {
      type: DataTypes.TINYINT,
      allowNull: false
    },
    auto_withdrawal_error: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    referenceId: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    transferId: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'withdraw_requests',
    timestamps: false
  });
};
