<?php
// 配置多组API密钥（请在此处填写您的API密钥）
$apiKeys = [
    "sk-第一个API密钥",
    "sk-第二个API密钥",
    "sk-第三个API密钥",
    // 在此处添加更多API密钥...
];
$saveDir = __DIR__ . '/download/'; // 图片保存目录
$savedImages = []; // 保存生成图片的路径数组

// 创建保存目录(如果不存在)
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

// API端点
$apiUrl = "https://api.siliconflow.cn/v1/images/generations";

// 处理表单提交
$generatedImages = [];
$error = null;
$usedApiKey = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($apiKeys)) {
    // 获取用户输入
    $prompt = $_POST['prompt'] ?? '';
    $n = $_POST['n'] ?? 1;
    $size = $_POST['size'] ?? '900x383';
    $steps = $_POST['steps'] ?? 30;
    $guidance = $_POST['guidance'] ?? 7;
    $seed = $_POST['seed'] ?? '';

    // 验证输入
    if (empty($prompt)) {
        $error = "提示词不能为空";
    } elseif (!in_array($size, ['1024x1024', '900x383'])) {
        $error = "无效的尺寸选项";
    } elseif ($n < 1 || $n > 4) {
        $error = "生成数量必须在1-4之间";
    } else {
        // 准备请求数据
        $data = [
            "model" => "Kwai-Kolors/Kolors",
            "prompt" => $prompt,
            "n" => (int)$n,
            "size" => $size,
            "steps" => (int)$steps,
            "guidance_scale" => (int)$guidance,
        ];

        // 如果有种子值则添加
        if (!empty($seed)) {
            $data['seed'] = (int)$seed;
        }

        // 尝试使用多个API密钥直到成功或全部失败
        $success = false;
        $lastError = null;
        $keysCount = count($apiKeys);
        
        // 为每个访客随机选择起始API密钥
        $startIndex = mt_rand(0, $keysCount - 1);
        $currentIndex = $startIndex;
        $attempts = 0;
        
        while ($attempts < $keysCount && !$success) {
            // 选择当前API密钥
            $currentApiKey = $apiKeys[$currentIndex];
            $usedApiKey = "密钥 #" . ($currentIndex + 1);
            
            // 发送API请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $currentApiKey"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $lastError = "cURL错误: " . curl_error($ch);
            } elseif ($httpCode !== 200) {
                $lastError = "API错误 [$httpCode]: " . $response;
            } else {
                $responseData = json_decode($response, true);
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    $generatedImages = $responseData['data'];
                    $success = true;
                    
                    // 保存图片到本地
                    foreach ($generatedImages as $image) {
                        if (isset($image['url'])) {
                            $imageUrl = $image['url'];
                            $imageContent = file_get_contents($imageUrl);
                            
                            if ($imageContent !== false) {
                                // 生成唯一文件名
                                $filename = 'kolors_' . uniqid() . '.png';
                                $filePath = $saveDir . $filename;
                                
                                // 保存文件
                                if (file_put_contents($filePath, $imageContent)) {
                                    $savedImages[] = $filePath;
                                } else {
                                    $error = "无法保存图片到本地目录";
                                }
                            } else {
                                $error = "无法下载生成的图片";
                            }
                        }
                    }
                    break; // 成功时跳出循环
                } else {
                    $lastError = "API返回数据格式不正确";
                }
            }
            
            curl_close($ch);
            
            // 移动到下一个API密钥（循环）
            $currentIndex = ($currentIndex + 1) % $keysCount;
            $attempts++;
        }
        
        if (!$success) {
            $error = "所有API密钥尝试失败。最后错误: " . $lastError;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信公众号封面图生成器——实战VIP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 基础样式重置 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4ecfb 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* 主容器 */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* 头部样式 */
        header {
            padding: 30px 40px;
            background: linear-gradient(120deg, #6a93cb 0%, #4b6cb7 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            z-index: 1;
        }
        
        header h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        /* 主内容布局 */
        .main-content {
            display: flex;
            flex-wrap: wrap;
            padding: 30px;
            gap: 30px;
        }
        
        .form-section, .result-section {
            flex: 1;
            min-width: 300px;
        }
        
        .form-section {
            background: #fafcff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(75, 108, 183, 0.08);
        }
        
        .result-section {
            display: flex;
            flex-direction: column;
        }
        
        /* 表单样式 */
        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            color: #4b6cb7;
            margin-bottom: 25px;
        }
        
        .form-title i {
            background: #e4ecfb;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #4a5568;
            font-size: 1.1rem;
        }
        
        textarea, input, select {
            width: 100%;
            padding: 16px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            transition: all 0.3s;
        }
        
        textarea {
            height: 150px;
            resize: vertical;
        }
        
        textarea:focus, input:focus, select:focus {
            outline: none;
            border-color: #6a93cb;
            box-shadow: 0 0 0 3px rgba(106, 147, 203, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        /* 按钮样式 */
        .btn {
            display: block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(to right, #6a93cb, #4b6cb7);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(75, 108, 183, 0.4);
            letter-spacing: 1px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(75, 108, 183, 0.5);
        }
        
        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 3px 10px rgba(75, 108, 183, 0.4);
        }
        
        /* 通知样式 */
        .error, .success, .api-key-notice {
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .error {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #e53e3e;
        }
        
        .success {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            color: #38a169;
        }
        
        .api-key-notice {
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            color: #3182ce;
        }
        
        /* 结果卡片 */
        .result-card {
            background: #fafcff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(75, 108, 183, 0.08);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .result-header {
            padding: 25px 30px;
            background: linear-gradient(120deg, #8aaee0 0%, #6a93cb 100%);
            color: white;
        }
        
        .result-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .result-content {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* 图像容器 - 优化多图布局 */
        .images-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
            height: 100%;
        }
        
        /* 单列布局 */
        .single-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
            height: 100%;
        }
        
        .single-column .image-item {
            width: 100%;
            flex: 1;
        }
        
        /* 双列布局 */
        .double-column {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            height: 100%;
        }
        
        .image-item {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .image-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .image-container {
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 比例 */
            position: relative;
            background: #f8fafc;
            overflow: hidden;
            cursor: pointer;
        }
        
        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .image-container:hover img {
            transform: scale(1.03);
        }
        
        .image-footer {
            padding: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        /* 下载按钮 */
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 25px;
            background: linear-gradient(to right, #38a169, #2f855a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(56, 161, 105, 0.3);
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(56, 161, 105, 0.4);
            background: linear-gradient(to right, #2f855a, #276749);
        }
        
        .download-btn:active {
            transform: translateY(0);
        }
        
        /* 占位符样式 */
        .image-placeholder {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 16px;
            border: 2px dashed #cbd5e0;
        }
        
        .image-placeholder svg {
            width: 100px;
            height: 100px;
            opacity: 0.5;
        }
        
        .info-text {
            text-align: center;
            padding: 30px 20px;
            color: #718096;
            font-size: 1.1rem;
        }
        
        .info-text p {
            margin-bottom: 15px;
        }
        
        /* 页脚样式 */
        footer {
            padding: 25px 40px;
            text-align: center;
            background: #f1f5f9;
            color: #4a5568;
            font-size: 1.05rem;
        }
        
        footer p {
            margin: 8px 0;
        }
        
        /* 响应式设计 */
        @media (max-width: 1100px) {
            .main-content {
                flex-direction: column;
            }
            
            .form-section, .result-section {
                flex: none;
                width: 100%;
            }
            
            header {
                padding: 25px 20px;
            }
            
            header h1 {
                font-size: 2.3rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            header h1 {
                font-size: 1.8rem;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .form-title i {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .double-column {
                grid-template-columns: 1fr;
            }
            
            textarea, input, select {
                padding: 14px 16px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 20px;
            }
            
            header {
                padding: 20px 15px;
            }
            
            header h1 {
                font-size: 1.6rem;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .download-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* 加载动画 */
        .loader {
            display: none;
            text-align: center;
            padding: 30px;
        }
        
        .loader-dots {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 20px;
        }
        
        .loader-dots div {
            position: absolute;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #4b6cb7;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }
        
        .loader-dots div:nth-child(1) {
            left: 8px;
            animation: loader-dots1 0.6s infinite;
        }
        
        .loader-dots div:nth-child(2) {
            left: 8px;
            animation: loader-dots2 0.6s infinite;
        }
        
        .loader-dots div:nth-child(3) {
            left: 32px;
            animation: loader-dots2 0.6s infinite;
        }
        
        .loader-dots div:nth-child(4) {
            left: 56px;
            animation: loader-dots3 0.6s infinite;
        }
        
        @keyframes loader-dots1 {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
        
        @keyframes loader-dots3 {
            0% { transform: scale(1); }
            100% { transform: scale(0); }
        }
        
        @keyframes loader-dots2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(24px, 0); }
        }
        
        /* 全屏图片查看器 */
        .image-viewer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .image-viewer.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .image-viewer img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 24px;
            color: white;
        }
        
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 24px;
            color: white;
            opacity: 0.7;
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            opacity: 1;
        }
        
        .prev-btn {
            left: 20px;
        }
        
        .next-btn {
            right: 20px;
        }
        
        .image-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 1.2rem;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px 15px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>微信公众号封面图生成器——实战VIP</h1>
            <p class="subtitle">使用Kwai-Kolors/Kolors模型从文字描述生成高质量图像</p>
        </header>
        
        <div class="main-content">
            <div class="form-section">
                <h2 class="form-title"><i class="fas fa-paint-brush"></i> 图像描述</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <strong><i class="fas fa-exclamation-circle"></i> 错误：</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php elseif (!empty($generatedImages)): ?>
                    <div class="success">
                        <strong><i class="fas fa-check-circle"></i> 成功！</strong> 已生成 <?php echo count($generatedImages); ?> 张图像
                        <?php if (!empty($usedApiKey)): ?>
                            <br><small>使用密钥: <?php echo htmlspecialchars($usedApiKey); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="image-form">
                    <div class="form-group">
                        <label for="prompt"><i class="fas fa-font"></i> 提示词（Prompt）</label>
                        <textarea 
                            id="prompt" 
                            name="prompt" 
                            placeholder="输入详细的图像描述...例如：一只穿着宇航服的柴犬在月球上漫步，星空背景，超现实风格"
                            required><?php echo isset($_POST['prompt']) ? htmlspecialchars($_POST['prompt']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="n"><i class="fas fa-image"></i> 生成数量 (1-4)</label>
                            <select id="n" name="n">
                                <option value="1" <?php if ((isset($_POST['n']) && $_POST['n'] == 1) || !isset($_POST['n'])) echo 'selected'; ?>>1张</option>
                                <option value="2" <?php if (isset($_POST['n']) && $_POST['n'] == 2) echo 'selected'; ?>>2张</option>
                                <option value="3" <?php if (isset($_POST['n']) && $_POST['n'] == 3) echo 'selected'; ?>>3张</option>
                                <option value="4" <?php if (isset($_POST['n']) && $_POST['n'] == 4) echo 'selected'; ?>>4张</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="size"><i class="fas fa-expand-arrows-alt"></i> 图像尺寸</label>
                            <select id="size" name="size">
                                <option value="900x383" <?php if ((isset($_POST['size']) && $_POST['size'] == '900x383') || !isset($_POST['size'])) echo 'selected'; ?>>900×383 像素</option>
                                <option value="1024x1024" <?php if (isset($_POST['size']) && $_POST['size'] == '1024x1024') echo 'selected'; ?>>1024×1024 像素</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="steps"><i class="fas fa-shoe-prints"></i> 迭代步数 (1-50)</label>
                            <input 
                                type="number" 
                                id="steps" 
                                name="steps" 
                                min="1" 
                                max="50" 
                                value="<?php echo isset($_POST['steps']) ? htmlspecialchars($_POST['steps']) : '30'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="guidance"><i class="fas fa-sliders-h"></i> 引导尺度 (1-20)</label>
                            <input 
                                type="number" 
                                id="guidance" 
                                name="guidance" 
                                min="1" 
                                max="20" 
                                step="0.5"
                                value="<?php echo isset($_POST['guidance']) ? htmlspecialchars($_POST['guidance']) : '7'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="seed"><i class="fas fa-seedling"></i> 随机种子（可选）</label>
                        <input 
                            type="number" 
                            id="seed" 
                            name="seed" 
                            placeholder="输入随机种子"
                            value="<?php echo isset($_POST['seed']) ? htmlspecialchars($_POST['seed']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-magic"></i> 生成图像
                    </button>
                </form>
                
                <?php if (empty($apiKeys)): ?>
                    <div class="api-key-notice">
                        <strong><i class="fas fa-exclamation-triangle"></i> 注意：</strong> 请先在PHP代码中设置您的API密钥
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="result-section">
                <div class="result-card">
                    <div class="result-header">
                        <h2><i class="fas fa-images"></i> 生成结果</h2>
                    </div>
                    <div class="result-content">
                        <?php if (!empty($generatedImages)): ?>
                            <div class="images-container">
                                <?php if (count($generatedImages) == 1): ?>
                                    <!-- 单张图片布局 -->
                                    <div class="single-column">
                                        <div class="image-item">
                                            <div class="image-container" data-index="0">
                                                <img src="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" alt="Generated Image 1">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" class="download-btn" download="kolors_1.png">
                                                    <i class="fas fa-download"></i> 下载图片
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif (count($generatedImages) == 2): ?>
                                    <!-- 两张图片布局：单列上下排列 -->
                                    <div class="single-column">
                                        <?php foreach ($generatedImages as $index => $image): ?>
                                            <div class="image-item">
                                                <div class="image-container" data-index="<?php echo $index; ?>">
                                                    <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Generated Image <?php echo $index + 1; ?>">
                                                </div>
                                                <div class="image-footer">
                                                    <a href="<?php echo htmlspecialchars($image['url']); ?>" class="download-btn" download="kolors_<?php echo $index+1; ?>.png">
                                                        <i class="fas fa-download"></i> 下载图片 <?php echo $index + 1; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (count($generatedImages) == 3): ?>
                                    <!-- 三张图片布局：第一行一张大图，第二行两张小图 -->
                                    <div class="single-column">
                                        <div class="image-item">
                                            <div class="image-container" data-index="0">
                                                <img src="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" alt="Generated Image 1">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" class="download-btn" download="kolors_1.png">
                                                    <i class="fas fa-download"></i> 下载图片 1
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="double-column">
                                        <div class="image-item">
                                            <div class="image-container" data-index="1">
                                                <img src="<?php echo htmlspecialchars($generatedImages[1]['url']); ?>" alt="Generated Image 2">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[1]['url']); ?>" class="download-btn" download="kolors_2.png">
                                                    <i class="fas fa-download"></i> 下载图片 2
                                                </a>
                                            </div>
                                        </div>
                                        <div class="image-item">
                                            <div class="image-container" data-index="2">
                                                <img src="<?php echo htmlspecialchars($generatedImages[2]['url']); ?>" alt="Generated Image 3">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[2]['url']); ?>" class="download-btn" download="kolors_3.png">
                                                    <i class="fas fa-download"></i> 下载图片 3
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- 四张图片布局：两行两列 -->
                                    <div class="double-column">
                                        <div class="image-item">
                                            <div class="image-container" data-index="0">
                                                <img src="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" alt="Generated Image 1">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[0]['url']); ?>" class="download-btn" download="kolors_1.png">
                                                    <i class="fas fa-download"></i> 下载图片 1
                                                </a>
                                            </div>
                                        </div>
                                        <div class="image-item">
                                            <div class="image-container" data-index="1">
                                                <img src="<?php echo htmlspecialchars($generatedImages[1]['url']); ?>" alt="Generated Image 2">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[1]['url']); ?>" class="download-btn" download="kolors_2.png">
                                                    <i class="fas fa-download"></i> 下载图片 2
                                                </a>
                                            </div>
                                        </div>
                                        <div class="image-item">
                                            <div class="image-container" data-index="2">
                                                <img src="<?php echo htmlspecialchars($generatedImages[2]['url']); ?>" alt="Generated Image 3">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[2]['url']); ?>" class="download-btn" download="kolors_3.png">
                                                    <i class="fas fa-download"></i> 下载图片 3
                                                </a>
                                            </div>
                                        </div>
                                        <div class="image-item">
                                            <div class="image-container" data-index="3">
                                                <img src="<?php echo htmlspecialchars($generatedImages[3]['url']); ?>" alt="Generated Image 4">
                                            </div>
                                            <div class="image-footer">
                                                <a href="<?php echo htmlspecialchars($generatedImages[3]['url']); ?>" class="download-btn" download="kolors_4.png">
                                                    <i class="fas fa-download"></i> 下载图片 4
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="image-placeholder">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </div>
                            <div class="info-text">
                                <p><i class="fas fa-info-circle"></i> 请在左侧输入图像描述并点击"生成图像"按钮</p>
                                <p><i class="fas fa-info-circle"></i> 生成的图像将在此处显示“下载图片”按钮</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="loader" id="loader">
                            <div class="loader-dots">
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                            <p style="margin-top: 20px; color: #4b6cb7; font-weight: 500;">正在生成图像，请稍候...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>Powered by www.shizhan.vip | 基于SiliconFlow API</p>
            <p>多密钥轮询系统已启用 | 总密钥数: <?php echo count($apiKeys); ?></p>
        </footer>
    </div>
    
    <!-- 全屏图片查看器 -->
    <div class="image-viewer" id="image-viewer">
        <div class="close-btn" id="close-viewer">
            <i class="fas fa-times"></i>
        </div>
        <div class="nav-btn prev-btn" id="prev-btn">
            <i class="fas fa-chevron-left"></i>
        </div>
        <div class="nav-btn next-btn" id="next-btn">
            <i class="fas fa-chevron-right"></i>
        </div>
        <img id="fullscreen-img" src="" alt="Fullscreen Image">
        <div class="image-counter" id="image-counter"></div>
    </div>
    
    <script>
        // 表单提交时显示加载动画
        document.getElementById('image-form').addEventListener('submit', function() {
            document.getElementById('loader').style.display = 'block';
        });
        
        // 全屏图片查看器功能
        const imageViewer = document.getElementById('image-viewer');
        const fullscreenImg = document.getElementById('fullscreen-img');
        const closeViewer = document.getElementById('close-viewer');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const imageCounter = document.getElementById('image-counter');
        
        let currentImageIndex = 0;
        let imageUrls = [];
        
        // 收集所有生成的图片URL
        document.querySelectorAll('.image-container img').forEach(img => {
            imageUrls.push(img.src);
        });
        
        // 图片点击事件
        document.querySelectorAll('.image-container').forEach(container => {
            container.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                currentImageIndex = index;
                showImage(currentImageIndex);
            });
        });
        
        // 显示图片
        function showImage(index) {
            if (imageUrls.length === 0) return;
            
            fullscreenImg.src = imageUrls[index];
            imageCounter.textContent = `${index + 1} / ${imageUrls.length}`;
            imageViewer.classList.add('active');
            document.body.style.overflow = 'hidden'; // 防止背景滚动
        }
        
        // 关闭查看器
        closeViewer.addEventListener('click', function() {
            imageViewer.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
        
        // 上一张
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentImageIndex = (currentImageIndex - 1 + imageUrls.length) % imageUrls.length;
            showImage(currentImageIndex);
        });
        
        // 下一张
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
            showImage(currentImageIndex);
        });
        
        // 键盘导航
        document.addEventListener('keydown', function(e) {
            if (!imageViewer.classList.contains('active')) return;
            
            if (e.key === 'Escape') {
                imageViewer.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
            
            if (e.key === 'ArrowLeft') {
                currentImageIndex = (currentImageIndex - 1 + imageUrls.length) % imageUrls.length;
                showImage(currentImageIndex);
            }
            
            if (e.key === 'ArrowRight') {
                currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
                showImage(currentImageIndex);
            }
        });
        
        // 点击背景关闭
        imageViewer.addEventListener('click', function(e) {
            if (e.target === imageViewer) {
                imageViewer.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>
