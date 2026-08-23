import asyncio
import threading
import os
import sys
import json
from datetime import datetime
from telethon import TelegramClient, events
from telethon.tl.types import Channel, Chat
import customtkinter as ctk
from tkinter import messagebox, scrolledtext
import pystray
from PIL import Image, ImageDraw

# ==================== 资源路径处理（打包后需要） ====================
def resource_path(relative_path):
    try:
        base_path = sys._MEIPASS
    except Exception:
        base_path = os.path.abspath(".")
    return os.path.join(base_path, relative_path)

# ==================== 配置 ====================
SESSION_NAME = "tg_monitor_session"
CONFIG_FILE = "config.json"

ctk.set_appearance_mode("System")
ctk.set_default_color_theme("blue")

class TelegramMonitorApp(ctk.CTk):
    def __init__(self):
        super().__init__()
        self.title("Telegram 群消息监听器")
        self.geometry("980x720")
        self.minsize(800, 600)

        # 尝试设置图标
        try:
            self.iconbitmap(resource_path("icon.ico"))
        except:
            pass

        self.client = None
        self.loop = None
        self.listening = False
        self.selected_chats = set()
        self.keywords = []
        self.tray_icon = None

        self.load_config()
        self.create_widgets()
        self.protocol("WM_DELETE_WINDOW", self.on_closing)

    def load_config(self):
        self.config = {
            "api_id": "",
            "api_hash": "",
            "phone": "",
            "keywords": "",
            "selected_chat_ids": []
        }
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                    self.config.update(json.load(f))
            except:
                pass

    def save_config(self):
        self.config["api_id"] = self.api_id_entry.get().strip()
        self.config["api_hash"] = self.api_hash_entry.get().strip()
        self.config["phone"] = self.phone_entry.get().strip()
        self.config["keywords"] = self.keyword_entry.get().strip()
        self.config["selected_chat_ids"] = list(self.selected_chats)
        with open(CONFIG_FILE, "w", encoding="utf-8") as f:
            json.dump(self.config, f, ensure_ascii=False, indent=2)

    def create_widgets(self):
        left = ctk.CTkFrame(self, width=320)
        left.pack(side="left", fill="y", padx=10, pady=10)
        left.pack_propagate(False)

        ctk.CTkLabel(left, text="登录信息", font=ctk.CTkFont(size=16, weight="bold")).pack(pady=(10, 5))

        ctk.CTkLabel(left, text="API ID").pack(anchor="w", padx=15)
        self.api_id_entry = ctk.CTkEntry(left, placeholder_text="从 my.telegram.org 获取")
        self.api_id_entry.pack(fill="x", padx=15, pady=2)
        self.api_id_entry.insert(0, self.config["api_id"])

        ctk.CTkLabel(left, text="API Hash").pack(anchor="w", padx=15, pady=(8, 0))
        self.api_hash_entry = ctk.CTkEntry(left, placeholder_text="从 my.telegram.org 获取")
        self.api_hash_entry.pack(fill="x", padx=15, pady=2)
        self.api_hash_entry.insert(0, self.config["api_hash"])

        ctk.CTkLabel(left, text="手机号 (带国家码)").pack(anchor="w", padx=15, pady=(8, 0))
        self.phone_entry = ctk.CTkEntry(left, placeholder_text="+86138xxxxxxxx")
        self.phone_entry.pack(fill="x", padx=15, pady=2)
        self.phone_entry.insert(0, self.config["phone"])

        self.login_btn = ctk.CTkButton(left, text="登录 / 连接", command=self.start_login)
        self.login_btn.pack(fill="x", padx=15, pady=15)

        ctk.CTkLabel(left, text="监听设置", font=ctk.CTkFont(size=16, weight="bold")).pack(pady=(15, 5))

        ctk.CTkLabel(left, text="关键词过滤 (空格或逗号分隔，留空=全部)").pack(anchor="w", padx=15)
        self.keyword_entry = ctk.CTkEntry(left, placeholder_text="例如: 招聘 BTC 信号")
        self.keyword_entry.pack(fill="x", padx=15, pady=2)
        self.keyword_entry.insert(0, self.config["keywords"])

        self.refresh_btn = ctk.CTkButton(left, text="刷新已加入的群", command=self.refresh_dialogs, state="disabled")
        self.refresh_btn.pack(fill="x", padx=15, pady=10)

        ctk.CTkLabel(left, text="选择要监听的群（可多选）").pack(anchor="w", padx=15)
        self.chat_listbox = ctk.CTkScrollableFrame(left, height=220)
        self.chat_listbox.pack(fill="both", expand=True, padx=15, pady=5)
        self.chat_vars = {}

        self.start_btn = ctk.CTkButton(left, text="开始监听", command=self.toggle_listen, state="disabled", fg_color="green")
        self.start_btn.pack(fill="x", padx=15, pady=15)

        self.status_label = ctk.CTkLabel(left, text="状态：未连接", text_color="gray")
        self.status_label.pack(pady=5)

        right = ctk.CTkFrame(self)
        right.pack(side="right", fill="both", expand=True, padx=(0, 10), pady=10)

        ctk.CTkLabel(right, text="实时消息日志", font=ctk.CTkFont(size=16, weight="bold")).pack(pady=10)

        self.log_text = scrolledtext.ScrolledText(right, wrap="word", font=("Consolas", 11), bg="#2b2b2b", fg="#e0e0e0")
        self.log_text.pack(fill="both", expand=True, padx=10, pady=(0, 10))

        btn_frame = ctk.CTkFrame(right, fg_color="transparent")
        btn_frame.pack(fill="x", padx=10, pady=(0, 10))
        ctk.CTkButton(btn_frame, text="清空日志", width=100, command=self.clear_log).pack(side="left")
        ctk.CTkButton(btn_frame, text="最小化到托盘", width=120, command=self.minimize_to_tray).pack(side="right")

    def log(self, msg):
        timestamp = datetime.now().strftime("%H:%M:%S")
        self.log_text.insert("end", f"[{timestamp}] {msg}\n")
        self.log_text.see("end")

    def clear_log(self):
        self.log_text.delete("1.0", "end")

    def start_login(self):
        api_id = self.api_id_entry.get().strip()
        api_hash = self.api_hash_entry.get().strip()
        phone = self.phone_entry.get().strip()

        if not api_id or not api_hash:
            messagebox.showerror("错误", "请填写 API ID 和 API Hash\n请到 https://my.telegram.org 申请")
            return

        self.save_config()
        self.login_btn.configure(state="disabled", text="连接中...")
        self.status_label.configure(text="状态：正在连接...", text_color="orange")

        def run_async():
            self.loop = asyncio.new_event_loop()
            asyncio.set_event_loop(self.loop)
            self.loop.run_until_complete(self.async_login(api_id, api_hash, phone))

        threading.Thread(target=run_async, daemon=True).start()

    async def async_login(self, api_id, api_hash, phone):
        try:
            self.client = TelegramClient(SESSION_NAME, int(api_id), api_hash)
            await self.client.start(phone=phone if phone else None)
            me = await self.client.get_me()
            self.after(0, lambda: self.on_login_success(me))
        except Exception as e:
            self.after(0, lambda: self.on_login_fail(str(e)))

    def on_login_success(self, me):
        self.log(f"✅ 登录成功：{me.first_name} (@{me.username or '无用户名'})")
        self.status_label.configure(text=f"状态：已登录 - {me.first_name}", text_color="green")
        self.login_btn.configure(state="normal", text="重新登录")
        self.refresh_btn.configure(state="normal")
        self.start_btn.configure(state="normal")
        self.refresh_dialogs()

    def on_login_fail(self, error):
        self.log(f"❌ 登录失败：{error}")
        self.status_label.configure(text="状态：登录失败", text_color="red")
        self.login_btn.configure(state="normal", text="登录 / 连接")
        messagebox.showerror("登录失败", error)

    def refresh_dialogs(self):
        if not self.client:
            return
        self.log("正在获取已加入的群聊...")
        for widget in self.chat_listbox.winfo_children():
            widget.destroy()
        self.chat_vars.clear()

        async def get_dialogs():
            dialogs = await self.client.get_dialogs()
            groups = []
            for d in dialogs:
                if isinstance(d.entity, (Channel, Chat)) and not getattr(d.entity, 'broadcast', False):
                    groups.append(d)
            self.after(0, lambda: self.populate_chat_list(groups))

        asyncio.run_coroutine_threadsafe(get_dialogs(), self.loop)

    def populate_chat_list(self, dialogs):
        for d in dialogs:
            chat_id = d.id
            title = d.title or str(chat_id)
            var = ctk.BooleanVar(value=chat_id in self.config.get("selected_chat_ids", []))
            cb = ctk.CTkCheckBox(self.chat_listbox, text=title[:40], variable=var,
                                 command=lambda cid=chat_id, v=var: self.toggle_chat(cid, v))
            cb.pack(anchor="w", padx=5, pady=2)
            self.chat_vars[chat_id] = var
            if var.get():
                self.selected_chats.add(chat_id)
        self.log(f"已加载 {len(dialogs)} 个群组")

    def toggle_chat(self, chat_id, var):
        if var.get():
            self.selected_chats.add(chat_id)
        else:
            self.selected_chats.discard(chat_id)
        self.save_config()

    def toggle_listen(self):
        if not self.listening:
            if not self.selected_chats:
                messagebox.showwarning("提示", "请至少选择一个群")
                return
            self.keywords = [k.strip() for k in self.keyword_entry.get().replace("，", ",").replace(" ", ",").split(",") if k.strip()]
            self.save_config()
            self.listening = True
            self.start_btn.configure(text="停止监听", fg_color="red")
            self.status_label.configure(text="状态：正在监听...", text_color="green")
            self.log(f"▶ 开始监听 {len(self.selected_chats)} 个群" + (f"，关键词：{self.keywords}" if self.keywords else "（全部消息）"))
            asyncio.run_coroutine_threadsafe(self.start_listening(), self.loop)
        else:
            self.listening = False
            self.start_btn.configure(text="开始监听", fg_color="green")
            self.status_label.configure(text="状态：已停止监听", text_color="orange")
            self.log("⏹ 已停止监听")

    async def start_listening(self):
        @self.client.on(events.NewMessage(chats=list(self.selected_chats)))
        async def handler(event):
            if not self.listening:
                return
            text = event.message.message or ""
            if self.keywords and not any(k.lower() in text.lower() for k in self.keywords):
                return
            sender = await event.get_sender()
            name = getattr(sender, "first_name", "") or getattr(sender, "title", "未知")
            chat = await event.get_chat()
            chat_title = getattr(chat, "title", str(event.chat_id))
            msg = f"【{chat_title}】{name}: {text[:300]}"
            self.after(0, lambda m=msg: self.log(m))

        self.log("事件处理器已注册，等待新消息...")

    def minimize_to_tray(self):
        self.withdraw()
        image = Image.new("RGB", (64, 64), color=(30, 144, 255))
        draw = ImageDraw.Draw(image)
        draw.rectangle([16, 16, 48, 48], fill="white")
        menu = pystray.Menu(
            pystray.MenuItem("显示窗口", self.show_window),
            pystray.MenuItem("退出", self.quit_app)
        )
        self.tray_icon = pystray.Icon("tg_monitor", image, "TG监听器", menu)
        threading.Thread(target=self.tray_icon.run, daemon=True).start()

    def show_window(self, icon=None, item=None):
        if self.tray_icon:
            self.tray_icon.stop()
        self.after(0, self.deiconify)

    def quit_app(self, icon=None, item=None):
        self.on_closing()

    def on_closing(self):
        self.save_config()
        self.listening = False
        if self.tray_icon:
            try:
                self.tray_icon.stop()
            except:
                pass
        if self.client and self.client.is_connected():
            try:
                asyncio.run_coroutine_threadsafe(self.client.disconnect(), self.loop)
            except:
                pass
        self.destroy()
        os._exit(0)

if __name__ == "__main__":
    app = TelegramMonitorApp()
    app.mainloop()