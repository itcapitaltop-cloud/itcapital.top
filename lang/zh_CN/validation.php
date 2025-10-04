<?php

return [
    'custom' => [
        'username' => [
            'required' => '请输入用户名',
            'regex'    => '仅允许拉丁字母、数字和 "_" 符号',
            'exists'   => '系统中不存在该用户名'
        ],

        'toUsername' => [
            'required' => '请输入用户名',
            'regex'    => '仅允许拉丁字母、数字和 "_" 符号',
            'exists'   => '系统中不存在该用户名',
            'not_self' => '不能将资金转给自己',
        ],

        'login' => [
            'required' => '请输入用户名或邮箱',
            'regex'    => '仅允许拉丁字母。',
            'credentials' => '登录/邮箱或密码无效'
        ],

        'telegram' => [
            'required' => '请输入 Telegram 用户名',
            'regex'    => '@ + 5‑32 个拉丁字母、数字或 _',
        ],

        'firstName' => [
            'required' => '请输入名字',
        ],
        'lastName' => [
            'required' => '请输入姓氏',
        ],

        'email' => [
            'required' => '请输入邮箱地址',
            'email'    => '请输入有效的邮箱地址。',
            'credentials' => '登录/邮箱或密码无效',
        ],

        'password' => [
            'required'  => '请输入密码',
            'min'       => '密码至少为 :min 个字符。',
            'regex'     => '密码必须包含至少一个数字，仅允许拉丁字母或符号 _ & @ * ^ % #。',
            'confirmed' => '密码不匹配。',
            'credentials' => '登录/邮箱或密码无效',
        ],

        'newPassword' => [
            'required'  => '请输入密码',
            'min'       => '密码至少为 :min 个字符。',
            'regex'     => '密码必须包含至少一个数字，仅允许拉丁字母或符号 _ & @ * ^ % #。',
            'confirmed' => '密码不匹配。',
        ],

        'passwordConfirm' => [
            'required'  => '请再次输入密码',
            'same'      => '密码不匹配。',
        ],

        'newPasswordConfirm' => [
            'required'  => '请再次输入密码',
            'same'      => '密码不匹配。',
        ],

        'depositAmount' => [
            'required' => '请输入充值金额',
            'numeric'  => '金额必须为数字',
            'min'      => '金额不能少于 :min',
        ],

        'amount' => [
            'required' => '请输入充值金额',
            'numeric'  => '金额必须为数字',
            'min'      => '金额不能少于 :min',
        ],

        'toPartnerAmount' => [
            'required' => '请输入转账金额',
            'numeric'  => '金额必须为数字',
            'balance'  => '合作伙伴余额不足',
        ],

        'toPackageAmount' => [
            'required' => '请输入转账金额',
            'numeric'  => '金额必须为数字',
            'balance'  => '合作伙伴余额不足',
        ],

        'withdrawAmount' => [
            'required' => '请输入提现金额',
            'numeric'  => '金额必须为数字',
            'min'      => '金额不能少于 :min',
        ],

        'withdrawPackageAmount' => [
            'required' => '请输入提现金额',
            'numeric'  => '金额必须为数字',
            'min'      => '金额不能少于 :min',
            'max_package_sum' => '金额不能超过套餐余额'
        ],

        'sbpPhone' => [
            'required_without' => '请输入电话号码',
            'string'   => '值必须为字符串',
            'regex'     => '格式不正确',
            'max'      => '字符长度不能超过 :max',
        ],

        'bankName' => [
            'required_without' => '请输入银行名称',
            'string'   => '值必须为字符串',
            'max'      => '字符长度不能超过 :max',
        ],

        'transactionHash' => [
            'required' => '请输入交易哈希或银行名称',
            'string'   => '值必须为字符串',
            'max'      => '字符长度不能超过 :max',
        ],

        'recipientName' => [
            'required_without' => '请输入收款人姓名',
            'string'   => '值必须为字符串',
            'max'      => '字符长度不能超过 :max',
        ],

        'address' => [
            'required_without_all' => '请输入钱包地址',
            'string'   => '值必须为字符串',
            'max'      => '字符长度不能超过 :max',
        ],
    ],

    'attributes' => [
        'username'        => '用户名',
        'email'           => '邮箱',
        'password'        => '密码',
        'passwordConfirm' => '确认密码',
    ],
];
