"use client";

import { Button } from "@/components/ui/button";
import { Logo } from "@/components/ui/logo";
import Image from "next/image";
import { cn } from "@/lib/utils";
import { useRouter } from "next/navigation";
import { useUser, useSelectedOrganization, useUserInitials } from "@/stores/user-store";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Building2, LayoutDashboard, LogOut, ChevronDown } from "lucide-react";
import { logoutAction } from "@/actions/auth";
import Link from "next/link";

interface PricingHeaderProps {
    checkingAuth?: boolean;
    className?: string;
    dark?: boolean;
}

export function PricingHeader({
    checkingAuth = false,
    className,
    dark = false
}: PricingHeaderProps) {
    const router = useRouter();
    const user = useUser();
    const selectedOrganization = useSelectedOrganization();
    const initials = useUserInitials();

    const handleLogout = async () => {
        await logoutAction();
        window.location.href = '/pricing';
    };

    const webUrl = process.env.NEXT_PUBLIC_WEB_URL || '/';

    return (
        <div className={cn(
            'flex justify-between items-center mb-12 md:mb-16 lg:mb-[90px] border-b border-border/50 pb-4 md:pb-6',
            className,
        )}>
            {dark ? (
                <Link
                    href={webUrl}
                    className="cursor-pointer flex items-center gap-1"
                >
                    <Image
                        src="/logo.svg"
                        alt={`${process.env.NEXT_PUBLIC_APP_NAME} Logo` || "Logo"}
                        width={34}
                        height={34}
                        className="size-7 md:size-10"
                    />
                    <Image
                        src="/logo-text.svg"
                        alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
                        width={110}
                        height={32}
                        className="h-6 md:h-8 filter brightness-0 invert pt-1.5"
                    />
                </Link>
            ) : (
                <Link
                    href={webUrl}
                    className="h-8 md:h-[54px] w-auto flex items-center justify-start"
                >
                    <div className="h-8 md:h-10 w-auto" >
                    <Logo />
                    </div>
                </Link>
            )}

            <div className="flex items-center gap-2 md:gap-4">
                {!checkingAuth && !user && (
                    <Button
                        variant="ghost"
                        className={cn(
                            'h-9 md:h-11 px-4 md:px-6 text-sm md:text-base rounded-lg',
                            dark && 'text-white',
                        )}
                        onClick={() => router.push('/login')}
                    >
                        Get started
                    </Button>
                )}

                {!checkingAuth && user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className={cn(
                                    'h-auto px-2 md:px-3 py-1.5 md:py-2 gap-1.5 md:gap-2 bg-transparent hover:bg-white/50 backdrop-blur-sm border border-gray-200/50 hover:border-gray-300/70 rounded-xl transition-all duration-200',
                                    dark && 'border-white/10 hover:bg-white/5 hover:border-white/20 text-white'
                                )}
                            >
                                <Avatar className="h-7 w-7 md:h-9 md:w-9 ring-2 ring-primary/10">
                                    <AvatarFallback className="bg-gradient-to-br from-primary to-primary/80 text-white text-xs md:text-sm font-semibold">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className=" flex-col items-start gap-0.5 hidden sm:flex">
                                    <span className="text-sm font-medium leading-none">
                                        {user.name || user.email}
                                    </span>
                                    {selectedOrganization?.current_plan && (
                                        <Badge variant="teal" className="mt-0.5 text-[10px] py-0 px-1.5 h-4 bg-teal-50/80 backdrop-blur-sm truncate">
                                            {selectedOrganization.current_plan.name}
                                        </Badge>
                                    )}
                                </div>
                                <ChevronDown className="h-3 w-3 md:h-4 md:w-4 ml-0.5 md:ml-1 opacity-40" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56 md:w-64 bg-white/95 backdrop-blur-md border-gray-200/70 shadow-xl">
                            <DropdownMenuLabel>
                                <div className="flex items-center gap-3 p-1">
                                    <Avatar className="h-12 w-12 ring-2 ring-primary/20">
                                        <AvatarFallback className="bg-gradient-to-br from-primary to-primary/80 text-white font-semibold text-base">
                                            {initials}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-sm font-semibold leading-none">{user.name}</p>
                                        <p className="text-xs leading-none text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator className="bg-gray-200/50" />
                            <DropdownMenuItem
                                onClick={() => router.push('/dashboard')}
                                className="cursor-pointer hover:bg-gray-100/70 transition-colors"
                            >
                                <LayoutDashboard className="mr-2 h-4 w-4 text-primary" />
                                Dashboard
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={() => router.push('/organizations')}
                                className="cursor-pointer hover:bg-gray-100/70 transition-colors"
                            >
                                <Building2 className="mr-2 h-4 w-4 text-primary" />
                                Switch Organization
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={handleLogout}
                                className="cursor-pointer hover:bg-gray-100/70 transition-colors"
                            >
                                <LogOut className="mr-2 h-4 w-4 text-destructive" />
                                Log out
                            </DropdownMenuItem>
                            {selectedOrganization && (
                                <>
                                    <DropdownMenuSeparator className="bg-gray-200/50" />
                                    <div className="px-2 py-3 bg-gradient-to-br from-teal-50/50 to-transparent rounded-md mx-1 my-1">
                                        <div className="text-xs font-medium text-muted-foreground mb-1.5">
                                            Current Organization
                                        </div>
                                        <div className="text-sm font-semibold text-gray-900 truncate">{selectedOrganization.name}</div>
                                        {selectedOrganization.current_plan && (
                                            <Badge variant="teal" className="mt-2 bg-teal-100/80 backdrop-blur-sm truncate">
                                                {selectedOrganization.current_plan.name} Plan
                                            </Badge>
                                        )}
                                    </div>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </div>
    );
}