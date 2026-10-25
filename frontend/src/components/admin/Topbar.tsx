"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { clearAdminToken, getAdminToken } from "@/lib/admin-auth";
import { logout } from "@/lib/api/auth";
import { getApiErrorMessage } from "@/lib/api/client";

export function Topbar({ onMenuClick }: { onMenuClick: () => void }) {
  const [notice, setNotice] = useState(false);
  const [busy, setBusy] = useState(false);
  const router = useRouter();
  async function signOut() {
    if (busy) return;
    setBusy(true);
    const token = getAdminToken();
    try { if (token) await logout(token); }
    catch (error) { setNotice(true); console.error(getApiErrorMessage(error, "Không thể thu hồi phiên đăng nhập.")); }
    finally { clearAdminToken(); router.replace("/admin/login"); setBusy(false); }
  }
  return (
    <header className="admin-topbar relative flex min-h-20 items-center justify-between gap-3 border-b px-4 sm:px-6">
      <div className="flex items-center gap-3">
        <button type="button" onClick={onMenuClick} className="ui-button-secondary rounded-lg px-3 py-2 text-sm lg:hidden">Menu</button>
        <div><p className="text-xs uppercase tracking-[0.2em] text-zinc-500">Khu vực quản trị · dữ liệu mẫu</p><h1 className="mt-1 text-lg font-semibold text-zinc-900 sm:text-xl">Sơn Phương Model</h1></div>
      </div>
      <div className="flex items-center gap-2 sm:gap-4">
        <button type="button" disabled={busy} onClick={() => void signOut()} className="ui-button-secondary px-3 py-2 text-xs sm:px-4 sm:text-sm">{busy ? "Đang đăng xuất…" : "Đăng xuất"}</button>
        <div className="hidden items-center gap-2 rounded-full border border-zinc-200 px-3 py-2 sm:flex"><span className="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-900 text-xs font-medium text-white">QT</span><span className="text-sm">Quản trị viên</span></div>
      </div>
      {notice && <p role="status" className="absolute right-4 top-[calc(100%+8px)] z-20 max-w-sm rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 shadow-lg">Phiên cục bộ đã kết thúc; máy chủ có thể chưa thu hồi token.</p>}
    </header>
  );
}
