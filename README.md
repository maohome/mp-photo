# mp-photo
# 微信公众号封面图生成器

![GitHub License](https://img.shields.io/github/license/yourusername/wechat-cover-generator)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Project Status](https://img.shields.io/badge/status-active-brightgreen)

一个基于SiliconFlow API的微信公众号封面图生成工具，使用Kwai-Kolors/Kolors模型从文本描述生成高质量封面图像。

## 项目截图

![项目截图](https://github.com/maohome/mp-photo/blob/main/screenshot.png)
![免费生图](https://github.com/maohome/mp-photo/blob/main/Kwai-Kolors.png)

## 主要功能

- 🎨 从文本描述生成高质量微信公众号封面图
- ⚡ 多API密钥自动轮询与故障转移
- 📥 一键下载生成的图片
- 🔍 全屏预览模式
- ⚙️ 可自定义生成参数：
  - 图片尺寸（900×383或1024×1024）
  - 生成数量（1-4张）
  - 迭代步数（1-50）
  - 引导尺度（1-20）
  - 随机种子

## 技术栈

- **后端**: PHP (cURL处理API请求)
- **前端**: HTML5, CSS3 (Flexbox/Grid布局), JavaScript
- **API**: [SiliconFlow](https://cloud.siliconflow.cn/i/NKETshYi)
- **模型**: Kwai-Kolors/Kolors

## 安装与使用

### 前提条件

- PHP 7.4+
- 可写目录权限
- SiliconFlow API密钥（[申请地址](https://cloud.siliconflow.cn/i/NKETshYi))

### 安装步骤

1. 克隆仓库：
```bash
git clone https://github.com/maohome/mp-photo.git
```

2. 进入项目目录：
```bash
cd mp-photo
```

3. 创建下载目录并设置权限：
```bash
mkdir download && chmod 755 download
```

4. 配置API密钥：
编辑 `index.php` 文件，在顶部添加您的API密钥：
```php
$apiKeys = [
    "sk-your-first-api-key",
    "sk-your-second-api-key",
    // 添加更多密钥...
];
```

5. 部署到Web服务器：
将项目文件上传到您的PHP服务器（如Apache或Nginx）

### 使用说明

1. 访问您的部署地址
2. 在表单中输入图片描述
3. 调整生成参数（可选）
4. 点击"生成图像"按钮
5. 查看并下载生成的图片

## 配置选项

在 `index.php` 文件中可以修改以下配置：

```php
// 图片保存目录
$saveDir = __DIR__ . '/download/';

// API端点（通常不需要修改）
$apiUrl = "https://api.siliconflow.cn/v1/images/generations";

// 默认生成参数
$data = [
    "model" => "Kwai-Kolors/Kolors",
    "prompt" => "", // 由用户输入
    "n" => 1,       // 生成数量
    "size" => "900x383", // 图片尺寸
    "steps" => 30,   // 迭代步数
    "guidance_scale" => 7, // 引导尺度
];
```

## 高级功能

### 多API密钥轮询

项目支持多API密钥自动轮询：
- 每个用户随机选择起始密钥
- 密钥无效时自动尝试下一个
- 所有密钥失败时显示错误

### 响应式设计

- 适配桌面、平板和手机设备
- 智能图片布局（1-4张不同布局）
- 触控友好的操作界面

## 贡献指南

欢迎贡献！请遵循以下步骤：

1. Fork 项目仓库
2. 创建特性分支 (`git checkout -b name/mp-photo`)
3. 提交更改 (`git commit -m 'Add some mp-photo'`)
4. 推送到分支 (`git push origin name/mp-photo`)
5. 提交 Pull Request

## 开源协议

本项目采用 [MIT 许可证](LICENSE)

## 常见问题

**Q: 如何获取API密钥？**  
A: 访问 [SiliconFlow](https://cloud.siliconflow.cn/i/NKETshYi)注册账号并申请API密钥。

**Q: 生成的图片保存在哪里？**  
A: 图片保存在服务器上的 `download/` 目录中，同时页面提供直接下载。

**Q: 支持哪些图片尺寸？**  
A: 目前支持900×383（公众号封面）和1024×1024（正方形）两种尺寸。

**Q: 最多可以生成多少张图片？**  
A: 每次请求最多生成4张图片。

## 项目结构

```
wechat-cover-generator/
├── download/                  # 图片保存目录
├── index.php                  # 主程序文件
├── LICENSE                    # 开源许可证
├── README.md                  # 项目文档
└── screenshot.jpg             # 项目截图
```

## 致谢

- [SiliconFlow](https://cloud.siliconflow.cn/i/NKETshYi) - 提供AI图像生成API
- [Font Awesome](https://fontawesome.com/) - 提供精美图标
- [Google Fonts](https://fonts.google.com/) - 提供优质字体

---
