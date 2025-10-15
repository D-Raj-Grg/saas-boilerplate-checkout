import React from "react";
import md5 from "md5";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";

interface GravatarAvatarProps {
  email: string;
  displayName: string;
  size?: number;
  defaultImage?: string;
  className?: string;
}

export default function GravatarAvatar({ 
  email, 
  displayName, 
  size = 48, 
  defaultImage = "404", 
  className = "" 
}: GravatarAvatarProps) {
  const getGravatarUrl = (email: string, size: number, defaultImage: string) => {
    const base = "https://www.gravatar.com/avatar/";
    const hash = md5(email?.trim()?.toLowerCase());
    return `${base}${hash}?s=${size * 8}&d=${defaultImage}`;
  };
  
  const gravatarUrl = getGravatarUrl(email, size, defaultImage);

  return (
    <Avatar className={cn(`w-${size} h-${size}`, className)}>
      <AvatarImage src={gravatarUrl} alt={displayName || "User Avatar"} />
      <AvatarFallback className="capitalize">
        {displayName?.charAt(0) || ""}
      </AvatarFallback>
    </Avatar>
  );
}