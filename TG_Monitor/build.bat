@echo off
chcp 65001 >nul
echo ========================================
echo   Telegram 群消息监听器 - 打包工具
echo ========================================
echo.

echo [1/3] 安装/更新依赖...
pip install -r requirements.txt -q

echo.
echo [2/3] 正在打包（文件夹模式，最稳定）...
echo.

:: 自动获取 customtkinter 路径
for /f "tokens=2*" %%a in ('pip show customtkinter ^| findstr Location') do set CTK_PATH=%%b

pyinstaller --noconfirm --onedir --windowed --name "TG群消息监听器" ^
--add-data "%CTK_PATH%\customtkinter;customtkinter/" ^
--hidden-import=darkdetect ^
--hidden-import=telethon ^
--hidden-import=pystray ^
--hidden-import=PIL ^
--collect-all telethon ^
--collect-all customtkinter ^
tg_monitor.py

echo.
echo [3/3] 打包完成！
echo.
echo 生成位置：dist\TG群消息监听器\
echo 把整个「TG群消息监听器」文件夹复制出去即可使用。
echo.
pause